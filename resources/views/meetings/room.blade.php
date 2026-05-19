@extends('layouts.app')

@section('content')
    <div
        id="meeting-room"
        class="min-h-[calc(100vh-8rem)] overflow-hidden rounded-lg border border-slate-200 bg-slate-950 text-white shadow-sm"
        data-meeting-id="{{ $meeting->id }}"
        data-peer-id="peer-{{ auth()->id() }}-{{ \Illuminate\Support\Str::uuid() }}"
        data-user-name="{{ auth()->user()->name }}"
        data-join-url="{{ route('meetings.room.join', $meeting) }}"
        data-heartbeat-url="{{ route('meetings.room.heartbeat', $meeting) }}"
        data-participants-url="{{ route('meetings.room.participants', $meeting) }}"
        data-signal-url="{{ route('meetings.room.signals.send', $meeting) }}"
        data-signal-poll-url="{{ route('meetings.room.signals.poll', $meeting) }}"
        data-ice-servers='@json($iceServers)'
    >
        <div class="grid min-h-[calc(100vh-8rem)] lg:grid-cols-[1fr_320px]">
            <main class="flex min-h-[560px] flex-col">
                <header class="flex flex-col gap-3 border-b border-white/10 bg-slate-900/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-blue-200">غرفة اجتماع مباشرة</p>
                        <h1 class="mt-1 truncate text-lg font-black text-white">{{ $meeting->title }}</h1>
                        <p class="mt-1 text-xs font-semibold text-slate-400">
                            {{ $meeting->starts_at->format('Y-m-d H:i') }}
                            @if($meeting->project)
                                <span class="mx-1">.</span>{{ $meeting->project->name }}
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('meetings.index') }}" class="inline-flex items-center justify-center rounded-lg border border-white/10 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/15">
                        الرجوع للاجتماعات
                    </a>
                </header>

                <section class="flex-1 p-4">
                    <div id="video-grid" class="grid h-full min-h-[420px] auto-rows-[minmax(220px,1fr)] gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <article id="local-tile" class="relative overflow-hidden rounded-lg border border-white/10 bg-slate-900 shadow-xl">
                            <video id="local-video" class="h-full min-h-[260px] w-full bg-slate-900 object-cover" autoplay muted playsinline></video>
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-slate-950/90 to-transparent p-3">
                                <div>
                                    <p class="text-sm font-black">أنت</p>
                                    <p id="local-state" class="mt-0.5 text-xs text-slate-300">لم يتم تشغيل الكاميرا بعد</p>
                                </div>
                                <span id="screen-badge" class="hidden rounded-full bg-blue-500 px-2.5 py-1 text-xs font-bold text-white">مشاركة شاشة</span>
                            </div>
                        </article>
                    </div>
                </section>

                <footer class="border-t border-white/10 bg-slate-900 px-4 py-4">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <button id="camera-button" type="button" class="rounded-lg bg-white px-4 py-3 text-sm font-black text-slate-950 transition hover:bg-slate-100">
                            تشغيل الكاميرا
                        </button>
                        <button id="mic-button" type="button" class="rounded-lg bg-white px-4 py-3 text-sm font-black text-slate-950 transition hover:bg-slate-100">
                            تشغيل المايك
                        </button>
                        <button id="screen-button" type="button" class="rounded-lg bg-blue-600 px-4 py-3 text-sm font-black text-white transition hover:bg-blue-700">
                            مشاركة الشاشة
                        </button>
                        <button id="leave-button" type="button" class="rounded-lg bg-red-600 px-4 py-3 text-sm font-black text-white transition hover:bg-red-700">
                            مغادرة
                        </button>
                    </div>
                    <p id="room-alert" class="mt-3 hidden text-center text-sm font-bold"></p>
                </footer>
            </main>

            <aside class="border-t border-white/10 bg-white text-slate-900 lg:border-r lg:border-t-0">
                <div class="border-b border-slate-100 p-5">
                    <h2 class="text-base font-black text-slate-950">المشاركون</h2>
                    <p class="mt-1 text-sm text-slate-500">الحضور المتصلون الآن وحالة الأجهزة.</p>
                </div>
                <div id="participants-list" class="max-h-[45vh] space-y-2 overflow-y-auto p-4 lg:max-h-[calc(100vh-18rem)]"></div>

                <div class="border-t border-slate-100 p-5">
                    <h3 class="text-sm font-black text-slate-950">محاور الاجتماع</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $meeting->agenda ?: 'لا توجد محاور محددة.' }}</p>

                    <h3 class="mt-5 text-sm font-black text-slate-950">الحضور المخطط</h3>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse($meeting->attendees as $attendee)
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-blue-100">{{ $attendee->name }}</span>
                        @empty
                            <span class="text-sm font-semibold text-slate-500">لم يتم تحديد حضور.</span>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const room = document.getElementById('meeting-room');
                const token = document.querySelector('meta[name="csrf-token"]').content;
                const peerId = room.dataset.peerId;
                const userName = room.dataset.userName;
                const iceServers = JSON.parse(room.dataset.iceServers);
                const endpoints = {
                    join: room.dataset.joinUrl,
                    heartbeat: room.dataset.heartbeatUrl,
                    participants: room.dataset.participantsUrl,
                    signal: room.dataset.signalUrl,
                    poll: room.dataset.signalPollUrl,
                };

                const videoGrid = document.getElementById('video-grid');
                const localVideo = document.getElementById('local-video');
                const localState = document.getElementById('local-state');
                const screenBadge = document.getElementById('screen-badge');
                const participantsList = document.getElementById('participants-list');
                const roomAlert = document.getElementById('room-alert');
                const cameraButton = document.getElementById('camera-button');
                const micButton = document.getElementById('mic-button');
                const screenButton = document.getElementById('screen-button');
                const leaveButton = document.getElementById('leave-button');

                const peers = new Map();
                const remoteStreams = new Map();
                let localStream = new MediaStream();
                let cameraTrack = null;
                let micTrack = null;
                let screenTrack = null;
                let lastSignalId = 0;
                let currentParticipants = [];
                let isLeaving = false;

                const showAlert = (message, type = 'info') => {
                    roomAlert.textContent = message;
                    roomAlert.className = type === 'error'
                        ? 'mt-3 text-center text-sm font-bold text-red-300'
                        : 'mt-3 text-center text-sm font-bold text-blue-200';
                    window.setTimeout(() => roomAlert.classList.add('hidden'), 4000);
                };

                const postJson = async (url, data) => {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify(data),
                    });

                    if (!response.ok) {
                        throw new Error(`Request failed: ${response.status}`);
                    }

                    return response.json();
                };

                const mediaState = () => ({
                    peer_id: peerId,
                    camera_enabled: Boolean(cameraTrack),
                    microphone_enabled: Boolean(micTrack),
                    screen_enabled: Boolean(screenTrack),
                });

                const refreshLocalVideo = () => {
                    const activeVideo = screenTrack || cameraTrack;
                    const nextStream = new MediaStream();

                    if (activeVideo) {
                        nextStream.addTrack(activeVideo);
                    }

                    if (micTrack) {
                        nextStream.addTrack(micTrack);
                    }

                    localStream = nextStream;
                    localVideo.srcObject = nextStream;
                    localState.textContent = [
                        cameraTrack ? 'الكاميرا تعمل' : 'الكاميرا متوقفة',
                        micTrack ? 'المايك يعمل' : 'المايك متوقف',
                    ].join(' . ');
                    screenBadge.classList.toggle('hidden', !screenTrack);
                };

                const replaceVideoTrack = (track) => {
                    peers.forEach((connection) => {
                        const sender = connection.getSenders().find((item) => item.track?.kind === 'video');

                        if (sender) {
                            sender.replaceTrack(track || null);
                        } else if (track) {
                            connection.addTrack(track, localStream);
                        }
                    });
                };

                const addAudioTrackToPeers = (track) => {
                    peers.forEach((connection) => {
                        const sender = connection.getSenders().find((item) => item.track?.kind === 'audio');

                        if (sender) {
                            sender.replaceTrack(track || null);
                        } else if (track) {
                            connection.addTrack(track, localStream);
                        }
                    });
                };

                const renegotiatePeers = () => {
                    currentParticipants
                        .filter((participant) => participant.peer_id !== peerId)
                        .forEach((participant) => startOffer(participant.peer_id).catch(() => {}));
                };

                const sendSignal = (receiverPeerId, type, payload) => postJson(endpoints.signal, {
                    sender_peer_id: peerId,
                    receiver_peer_id: receiverPeerId,
                    type,
                    payload,
                }).catch(() => {});

                const ensureRemoteTile = (remotePeerId, participantName) => {
                    let tile = document.querySelector(`[data-remote-tile="${remotePeerId}"]`);

                    if (tile) {
                        return tile.querySelector('video');
                    }

                    tile = document.createElement('article');
                    tile.dataset.remoteTile = remotePeerId;
                    tile.className = 'relative overflow-hidden rounded-lg border border-white/10 bg-slate-900 shadow-xl';
                    tile.innerHTML = `
                        <video class="h-full min-h-[260px] w-full bg-slate-900 object-cover" autoplay playsinline></video>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/90 to-transparent p-3">
                            <p class="text-sm font-black">${participantName || 'مشارك'}</p>
                            <p class="mt-0.5 text-xs text-slate-300">متصل</p>
                        </div>
                    `;
                    videoGrid.appendChild(tile);

                    return tile.querySelector('video');
                };

                const closePeer = (remotePeerId) => {
                    const connection = peers.get(remotePeerId);

                    if (connection) {
                        connection.close();
                    }

                    peers.delete(remotePeerId);
                    remoteStreams.delete(remotePeerId);
                    document.querySelector(`[data-remote-tile="${remotePeerId}"]`)?.remove();
                };

                const createPeerConnection = (remotePeerId) => {
                    if (peers.has(remotePeerId)) {
                        return peers.get(remotePeerId);
                    }

                    const participant = currentParticipants.find((item) => item.peer_id === remotePeerId);
                    const connection = new RTCPeerConnection({ iceServers });
                    peers.set(remotePeerId, connection);

                    localStream.getTracks().forEach((track) => connection.addTrack(track, localStream));

                    connection.onicecandidate = (event) => {
                        if (event.candidate) {
                            sendSignal(remotePeerId, 'candidate', event.candidate.toJSON());
                        }
                    };

                    connection.ontrack = (event) => {
                        const stream = remoteStreams.get(remotePeerId) || new MediaStream();
                        stream.addTrack(event.track);
                        remoteStreams.set(remotePeerId, stream);

                        const video = ensureRemoteTile(remotePeerId, participant?.display_name);
                        video.srcObject = stream;
                    };

                    connection.onconnectionstatechange = () => {
                        if (['closed', 'failed', 'disconnected'].includes(connection.connectionState)) {
                            closePeer(remotePeerId);
                        }
                    };

                    return connection;
                };

                const startOffer = async (remotePeerId) => {
                    const connection = createPeerConnection(remotePeerId);

                    if (connection.signalingState !== 'stable') {
                        return;
                    }

                    const offer = await connection.createOffer();
                    await connection.setLocalDescription(offer);
                    await sendSignal(remotePeerId, 'offer', offer);
                };

                const handleSignal = async (signal) => {
                    const remotePeerId = signal.sender_peer_id;

                    if (remotePeerId === peerId) {
                        return;
                    }

                    const connection = createPeerConnection(remotePeerId);

                    if (signal.type === 'offer') {
                        await connection.setRemoteDescription(new RTCSessionDescription(signal.payload));
                        const answer = await connection.createAnswer();
                        await connection.setLocalDescription(answer);
                        await sendSignal(remotePeerId, 'answer', answer);
                    }

                    if (signal.type === 'answer' && connection.signalingState !== 'stable') {
                        await connection.setRemoteDescription(new RTCSessionDescription(signal.payload));
                    }

                    if (signal.type === 'candidate' && signal.payload) {
                        await connection.addIceCandidate(new RTCIceCandidate(signal.payload));
                    }
                };

                const pollSignals = async () => {
                    const url = new URL(endpoints.poll, window.location.origin);
                    url.searchParams.set('peer_id', peerId);
                    url.searchParams.set('after_id', lastSignalId);

                    const response = await fetch(url, { headers: { Accept: 'application/json' } });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();

                    for (const signal of payload.signals) {
                        lastSignalId = Math.max(lastSignalId, signal.id);
                        await handleSignal(signal).catch(() => {});
                    }
                };

                const renderParticipants = (participants) => {
                    participantsList.innerHTML = participants.map((participant) => `
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-slate-950">${participant.peer_id === peerId ? 'أنت' : participant.display_name}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">${participant.screen_enabled ? 'يشارك الشاشة' : 'متصل'}</p>
                                </div>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            </div>
                            <div class="mt-3 flex gap-2 text-xs font-bold">
                                <span class="rounded-full ${participant.camera_enabled ? 'bg-blue-50 text-blue-700 ring-blue-100' : 'bg-slate-100 text-slate-500 ring-slate-200'} px-2 py-1 ring-1">كاميرا</span>
                                <span class="rounded-full ${participant.microphone_enabled ? 'bg-blue-50 text-blue-700 ring-blue-100' : 'bg-slate-100 text-slate-500 ring-slate-200'} px-2 py-1 ring-1">مايك</span>
                            </div>
                        </div>
                    `).join('');
                };

                const syncParticipants = async () => {
                    const response = await fetch(endpoints.participants, { headers: { Accept: 'application/json' } });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    currentParticipants = payload.participants;
                    renderParticipants(currentParticipants);

                    const remoteParticipants = currentParticipants.filter((participant) => participant.peer_id !== peerId);
                    const activePeerIds = new Set(remoteParticipants.map((participant) => participant.peer_id));

                    peers.forEach((_, remotePeerId) => {
                        if (!activePeerIds.has(remotePeerId)) {
                            closePeer(remotePeerId);
                        }
                    });

                    remoteParticipants.forEach((participant) => {
                        createPeerConnection(participant.peer_id);

                        if (peerId < participant.peer_id) {
                            startOffer(participant.peer_id).catch(() => {});
                        }
                    });
                };

                const heartbeat = () => postJson(endpoints.heartbeat, mediaState()).catch(() => {});

                const joinRoom = async () => {
                    await postJson(endpoints.join, mediaState());
                    await syncParticipants();
                    showAlert('تم دخول غرفة الاجتماع.');
                };

                cameraButton.addEventListener('click', async () => {
                    try {
                        if (cameraTrack) {
                            cameraTrack.stop();
                            cameraTrack = null;
                            if (!screenTrack) {
                                replaceVideoTrack(null);
                            }
                            cameraButton.textContent = 'تشغيل الكاميرا';
                        } else {
                            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                            cameraTrack = stream.getVideoTracks()[0];
                            if (!screenTrack) {
                                replaceVideoTrack(cameraTrack);
                            }
                            cameraButton.textContent = 'إيقاف الكاميرا';
                        }

                        refreshLocalVideo();
                        renegotiatePeers();
                        await heartbeat();
                    } catch (error) {
                        showAlert('تعذر تشغيل الكاميرا. تحقق من صلاحيات المتصفح.', 'error');
                    }
                });

                micButton.addEventListener('click', async () => {
                    try {
                        if (micTrack) {
                            micTrack.stop();
                            micTrack = null;
                            addAudioTrackToPeers(null);
                            micButton.textContent = 'تشغيل المايك';
                        } else {
                            const stream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
                            micTrack = stream.getAudioTracks()[0];
                            addAudioTrackToPeers(micTrack);
                            micButton.textContent = 'إيقاف المايك';
                        }

                        refreshLocalVideo();
                        renegotiatePeers();
                        await heartbeat();
                    } catch (error) {
                        showAlert('تعذر تشغيل المايك. تحقق من صلاحيات المتصفح.', 'error');
                    }
                });

                screenButton.addEventListener('click', async () => {
                    try {
                        if (screenTrack) {
                            screenTrack.stop();
                            screenTrack = null;
                            replaceVideoTrack(cameraTrack);
                            screenButton.textContent = 'مشاركة الشاشة';
                        } else {
                            const stream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
                            screenTrack = stream.getVideoTracks()[0];
                            screenTrack.onended = async () => {
                                screenTrack = null;
                                replaceVideoTrack(cameraTrack);
                                refreshLocalVideo();
                                renegotiatePeers();
                                screenButton.textContent = 'مشاركة الشاشة';
                                await heartbeat();
                            };
                            replaceVideoTrack(screenTrack);
                            screenButton.textContent = 'إيقاف المشاركة';
                        }

                        refreshLocalVideo();
                        renegotiatePeers();
                        await heartbeat();
                    } catch (error) {
                        showAlert('تعذر مشاركة الشاشة أو تم إلغاء الطلب.', 'error');
                    }
                });

                leaveButton.addEventListener('click', () => {
                    isLeaving = true;
                    [...localStream.getTracks(), cameraTrack, micTrack, screenTrack].filter(Boolean).forEach((track) => track.stop());
                    peers.forEach((connection) => connection.close());
                    window.location.href = '{{ route('meetings.index') }}';
                });

                window.addEventListener('beforeunload', () => {
                    isLeaving = true;
                });

                refreshLocalVideo();
                joinRoom().catch(() => showAlert('تعذر دخول الغرفة. أعد تحميل الصفحة.', 'error'));
                window.setInterval(() => {
                    if (!isLeaving) {
                        heartbeat();
                    }
                }, 2500);
                window.setInterval(() => {
                    if (!isLeaving) {
                        syncParticipants();
                        pollSignals();
                    }
                }, 1000);
            })();
        </script>
    @endpush
@endsection
