<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\MeetingSignal;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $meetingsQuery = Meeting::with(['project', 'organizer', 'attendees'])
            ->when($user->role !== 'admin', function ($query) use ($user) {
                $query->where(function ($scope) use ($user) {
                    $scope->where('organizer_id', $user->id)
                        ->orWhereHas('attendees', fn ($attendees) => $attendees->where('users.id', $user->id));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('agenda', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw("CASE WHEN status = 'scheduled' THEN 0 WHEN status = 'completed' THEN 1 ELSE 2 END")
            ->orderBy('starts_at');

        $meetings = $meetingsQuery->paginate(10)->withQueryString();

        $visibleMeetings = Meeting::query()
            ->when($user->role !== 'admin', function ($query) use ($user) {
                $query->where(function ($scope) use ($user) {
                    $scope->where('organizer_id', $user->id)
                        ->orWhereHas('attendees', fn ($attendees) => $attendees->where('users.id', $user->id));
                });
            });

        return view('meetings.index', [
            'meetings' => $meetings,
            'users' => User::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'statuses' => Meeting::statusLabels(),
            'filters' => $request->only(['search', 'status', 'project_id']),
            'stats' => [
                'total' => (clone $visibleMeetings)->count(),
                'scheduled' => (clone $visibleMeetings)->where('status', Meeting::STATUS_SCHEDULED)->count(),
                'completed' => (clone $visibleMeetings)->where('status', Meeting::STATUS_COMPLETED)->count(),
                'cancelled' => (clone $visibleMeetings)->where('status', Meeting::STATUS_CANCELLED)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMeetingManagement($request);

        $validated = $this->validateMeeting($request);
        $attendees = $validated['attendee_ids'] ?? [];
        unset($validated['attendee_ids']);

        $meeting = Meeting::create($validated + ['organizer_id' => $request->user()->id]);
        $meeting->attendees()->sync($attendees);

        return redirect()->route('meetings.index')->with('success', 'تمت جدولة الاجتماع بنجاح.');
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeMeetingAccess($request, $meeting);

        if ($request->user()->role !== 'admin' && $meeting->organizer_id !== $request->user()->id) {
            $validated = $request->validate([
                'status' => ['required', Rule::in([Meeting::STATUS_COMPLETED])],
                'summary' => ['required', 'string'],
                'decisions' => ['nullable', 'string'],
                'next_steps' => ['nullable', 'string'],
            ]);

            $meeting->update($validated);

            return redirect()->route('meetings.index')->with('success', 'تم حفظ ملخص الاجتماع.');
        }

        $validated = $this->validateMeeting($request, $meeting);
        $attendees = $validated['attendee_ids'] ?? [];
        unset($validated['attendee_ids']);

        $meeting->update($validated);
        $meeting->attendees()->sync($attendees);

        return redirect()->route('meetings.index')->with('success', 'تم تحديث الاجتماع بنجاح.');
    }

    public function destroy(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeMeetingManagement($request);

        $meeting->delete();

        return redirect()->route('meetings.index')->with('success', 'تم حذف الاجتماع.');
    }

    public function room(Request $request, Meeting $meeting)
    {
        $this->authorizeMeetingAccess($request, $meeting);

        $meeting->load(['project', 'organizer', 'attendees']);

        return view('meetings.room', [
            'meeting' => $meeting,
            'iceServers' => [
                ['urls' => 'stun:stun.l.google.com:19302'],
                ['urls' => 'stun:stun1.l.google.com:19302'],
            ],
        ]);
    }

    public function joinRoom(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeMeetingAccess($request, $meeting);

        $validated = $request->validate([
            'peer_id' => ['required', 'string', 'max:80'],
            'camera_enabled' => ['boolean'],
            'microphone_enabled' => ['boolean'],
            'screen_enabled' => ['boolean'],
        ]);

        $participant = MeetingParticipant::updateOrCreate(
            ['peer_id' => $validated['peer_id']],
            [
                'meeting_id' => $meeting->id,
                'user_id' => $request->user()->id,
                'display_name' => $request->user()->name,
                'camera_enabled' => $validated['camera_enabled'] ?? false,
                'microphone_enabled' => $validated['microphone_enabled'] ?? false,
                'screen_enabled' => $validated['screen_enabled'] ?? false,
                'last_seen_at' => now(),
            ]
        );

        MeetingSignal::where('meeting_id', $meeting->id)
            ->where(function ($query) use ($validated) {
                $query->where('sender_peer_id', $validated['peer_id'])
                    ->orWhere('receiver_peer_id', $validated['peer_id']);
            })
            ->where('created_at', '<', now()->subMinutes(15))
            ->delete();

        return response()->json([
            'participant' => $this->formatParticipant($participant),
        ]);
    }

    public function heartbeat(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeMeetingAccess($request, $meeting);

        $validated = $request->validate([
            'peer_id' => ['required', 'string', 'max:80'],
            'camera_enabled' => ['boolean'],
            'microphone_enabled' => ['boolean'],
            'screen_enabled' => ['boolean'],
        ]);

        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('peer_id', $validated['peer_id'])
            ->firstOrFail();

        $participant->update([
            'camera_enabled' => $validated['camera_enabled'] ?? false,
            'microphone_enabled' => $validated['microphone_enabled'] ?? false,
            'screen_enabled' => $validated['screen_enabled'] ?? false,
            'last_seen_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function participants(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeMeetingAccess($request, $meeting);

        MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('last_seen_at', '<', now()->subSeconds(20))
            ->delete();

        $participants = MeetingParticipant::where('meeting_id', $meeting->id)
            ->orderBy('display_name')
            ->get()
            ->map(fn (MeetingParticipant $participant) => $this->formatParticipant($participant));

        return response()->json(['participants' => $participants]);
    }

    public function sendSignal(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeMeetingAccess($request, $meeting);

        $validated = $request->validate([
            'sender_peer_id' => ['required', 'string', 'max:80'],
            'receiver_peer_id' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(['offer', 'answer', 'candidate'])],
            'payload' => ['required', 'array'],
        ]);

        MeetingSignal::create($validated + ['meeting_id' => $meeting->id]);

        return response()->json(['ok' => true]);
    }

    public function pollSignals(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeMeetingAccess($request, $meeting);

        $validated = $request->validate([
            'peer_id' => ['required', 'string', 'max:80'],
            'after_id' => ['nullable', 'integer'],
        ]);

        $signals = MeetingSignal::where('meeting_id', $meeting->id)
            ->where('receiver_peer_id', $validated['peer_id'])
            ->where('id', '>', $validated['after_id'] ?? 0)
            ->orderBy('id')
            ->limit(100)
            ->get(['id', 'sender_peer_id', 'receiver_peer_id', 'type', 'payload']);

        return response()->json(['signals' => $signals]);
    }

    public function streamParticipants(Request $request, Meeting $meeting)
    {
        $this->authorizeMeetingAccess($request, $meeting);

        return response()->noContent();
    }

    public function streamSignals(Request $request, Meeting $meeting)
    {
        $this->authorizeMeetingAccess($request, $meeting);

        return response()->noContent();
    }

    private function validateMeeting(Request $request, ?Meeting $meeting = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Meeting::statusLabels()))],
            'summary' => [$request->input('status') === Meeting::STATUS_COMPLETED ? 'required' : 'nullable', 'string'],
            'decisions' => ['nullable', 'string'],
            'next_steps' => ['nullable', 'string'],
            'attendee_ids' => ['nullable', 'array'],
            'attendee_ids.*' => ['exists:users,id'],
        ]);
    }

    private function authorizeMeetingManagement(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }

    private function authorizeMeetingAccess(Request $request, Meeting $meeting): void
    {
        $user = $request->user();

        abort_unless(
            $user?->role === 'admin'
            || $meeting->organizer_id === $user?->id
            || $meeting->attendees()->where('users.id', $user?->id)->exists(),
            403
        );
    }

    private function formatParticipant(MeetingParticipant $participant): array
    {
        return [
            'peer_id' => $participant->peer_id,
            'user_id' => $participant->user_id,
            'display_name' => $participant->display_name,
            'camera_enabled' => $participant->camera_enabled,
            'microphone_enabled' => $participant->microphone_enabled,
            'screen_enabled' => $participant->screen_enabled,
            'is_recent' => $participant->last_seen_at?->gt(now()->subSeconds(20)) ?? false,
        ];
    }
}
