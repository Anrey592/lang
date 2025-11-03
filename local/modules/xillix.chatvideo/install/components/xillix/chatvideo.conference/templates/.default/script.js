class ChatVideoConference {
    constructor(options) {
        this.signedParameters = options.signedParameters;
        this.voximplantAccountId = options.voximplantAccountId;
        this.voximplantAppId = options.voximplantAppId;
        this.currentRoom = null;
        this.voximplantClient = null;
        this.isVoximplantLoaded = false;
        this.isConnected = false;
        this.currentCall = null;
        this.currentUserID = null;

        this.init();
    }

    async init() {
        console.log('=== CHAT VIDEO CONFERENCE INIT ===');
        console.log('Account ID:', this.voximplantAccountId);
        console.log('App ID:', this.voximplantAppId);

        // Сначала загружаем SDK, потом биндим события
        await this.loadVoximplantSDK();
        this.bindEvents();

        const roomHash = this.getRoomHashFromURL();
        if (roomHash) {
            await this.joinRoom(roomHash);
        }
    }

    async loadVoximplantSDK() {
        return new Promise((resolve, reject) => {
            // Проверяем, может SDK уже загружен
            if (typeof VoxImplant !== 'undefined') {
                console.log('✅ VoxImplant SDK already loaded');
                this.isVoximplantLoaded = true;
                resolve(true);
                return;
            }

            console.log('🔄 Loading VoxImplant SDK...');
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/voximplant-websdk';

            script.onload = () => {
                console.log('✅ VoxImplant SDK script loaded');
                // Даем время на полную инициализацию SDK
                setTimeout(() => {
                    if (typeof VoxImplant !== 'undefined') {
                        this.isVoximplantLoaded = true;
                        console.log('✅ VoxImplant SDK fully initialized');
                        resolve(true);
                    } else {
                        reject(new Error('VoxImplant not defined after script load'));
                    }
                }, 1000);
            };

            script.onerror = (error) => {
                console.error('❌ Failed to load VoxImplant SDK:', error);
                reject(new Error('Failed to load VoxImplant SDK'));
            };

            document.head.appendChild(script);
        });
    }

    bindEvents() {
        const createRoomBtn = document.getElementById('create-room-btn');
        if (createRoomBtn) {
            createRoomBtn.addEventListener('click', () => this.createRoom());
        }

        const leaveRoomBtn = document.getElementById('leave-room');
        if (leaveRoomBtn) {
            leaveRoomBtn.addEventListener('click', () => this.leaveRoom());
        }

        const toggleAudioBtn = document.getElementById('toggle-audio');
        const toggleVideoBtn = document.getElementById('toggle-video');

        if (toggleAudioBtn) {
            toggleAudioBtn.addEventListener('click', () => this.toggleAudio());
        }

        if (toggleVideoBtn) {
            toggleVideoBtn.addEventListener('click', () => this.toggleVideo());
        }
    }

    getRoomHashFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('room');
    }

    async createRoom() {
        const roomName = document.getElementById('room-name')?.value || 'Новая комната';
        const maxParticipants = document.getElementById('max-participants')?.value || 10;

        try {
            const result = await BX.ajax.runComponentAction('xillix:chatvideo.conference', 'createRoom', {
                mode: 'class',
                data: {
                    roomName: roomName,
                    maxParticipants: parseInt(maxParticipants)
                }
            });

            if (result.data && result.data.success) {
                this.currentRoom = result.data.room;
                this.generateRoomLink();
                await this.initVoximplant();
            } else {
                this.showError(result.data?.error || 'Unknown error');
            }
        } catch (error) {
            console.error('Error creating room:', error);
            this.showError('Ошибка при создании комнаты: ' + error.message);
        }
    }

    async joinRoom(roomHash) {
        console.log('🔄 Joining room with hash:', roomHash);

        try {
            const result = await BX.ajax.runComponentAction('xillix:chatvideo.conference', 'joinRoom', {
                mode: 'class',
                data: {
                    roomHash: roomHash
                }
            });

            console.log('✅ Join room response:', result.data);

            if (result.data && result.data.success) {
                this.currentRoom = result.data.room;
                console.log('✅ Joined room successfully:', this.currentRoom);

                // ✅ Сохраняем ID текущего пользователя для фильтрации
                this.currentUserID = result.data.participant?.UF_USER_ID || this.getCurrentUserID();
                console.log('✅ Current user ID:', this.currentUserID);

                await this.initVoximplant();
            } else {
                console.error('❌ Failed to join room:', result.data?.error);
                this.showError(result.data?.error || 'Unknown error');
            }
        } catch (error) {
            console.error('❌ Error joining room:', error);
            this.showError('Ошибка при присоединении к комнате: ' + error.message);
        }
    }

    getCurrentUserID() {
        // Если currentUserID уже установлен, используем его
        if (this.currentUserID) {
            return this.currentUserID;
        }

        let userId = 0;

        // 1. Из данных компонента (передавайте из PHP)
        const container = document.getElementById('chat-video-conference');
        if (container) {
            const userIdAttr = container.getAttribute('data-user-id');
            if (userIdAttr) {
                userId = parseInt(userIdAttr);
                console.log('✅ User ID from data attribute:', userId);
            }
        }

        // 2. Из Bitrix глобального объекта
        if (!userId && window.BX && window.BX.message && window.BX.message.USER_ID) {
            userId = parseInt(window.BX.message.USER_ID);
            console.log('✅ User ID from BX.message:', userId);
        }

        // 3. Fallback - случайный ID
        if (!userId || userId === 0) {
            userId = Math.floor(Math.random() * 1000) + 1;
            console.warn('⚠️ Using random user ID:', userId);
        }

        // Сохраняем для будущего использования
        this.currentUserID = userId;
        return userId;
    }

    async leaveRoom() {
        if (!this.currentRoom) return;

        try {
            await BX.ajax.runComponentAction('xillix:chatvideo.conference', 'leaveRoom', {
                mode: 'class',
                data: {
                    roomHash: this.currentRoom.hash
                }
            });

            // Отключаемся от VoxImplant если подключены
            if (this.voximplantClient && this.isConnected) {
                if (this.currentCall) {
                    this.currentCall.hangup();
                    this.currentCall = null;
                }
                this.voximplantClient.disconnect();
                this.isConnected = false;
            }

            this.stopLocalVideo();
            this.showRoomCreation();
            this.currentRoom = null;
            this.showSuccess('Вы вышли из комнаты');

        } catch (error) {
            console.error('Error leaving room:', error);
            this.showError('Ошибка при выходе из комнаты');
        }
    }

    async initVoximplant() {
        console.log('=== INITIALIZING VOXIMPLANT ===');

        try {
            this.currentUserID = this.getCurrentUserID();
            this.voximplantClient = VoxImplant.getInstance();
            console.log('✅ VoxImplant client instance created');

            // Настраиваем обработчики событий
            this.setupVoximplantEventHandlers();

            // Инициализируем SDK
            const ACCOUNT_NODE = VoxImplant.ConnectionNode.NODE_8;
            await this.voximplantClient.init({
                node: ACCOUNT_NODE,
                showDebugInfo: true,
                progressTone: false,
                videoSupport: true
            });
            console.log('✅ VoxImplant SDK initialized');

            // ✅ ПОДКЛЮЧАЕМСЯ ПОД КОНКРЕТНЫМ ПОЛЬЗОВАТЕЛЕМ
            const username = 'admin'; // Замените на вашего пользователя
            const password = ""; // Замените на пароль (если есть)

            console.log('🔐 Connecting with user:', username);

            if (password) {
                // Если есть пароль
                await this.voximplantClient.login(username, password);
            } else {
                // Если пароля нет (только username)
                await this.voximplantClient.connect(username);
            }

            console.log('✅ User authentication initiated');
        } catch (error) {
            console.error('❌ Voximplant initialization error:', error);
            this.showError(`Ошибка инициализации Voximplant: ${error.message}`);

            // Fallback
            this.showConferenceInterface();
            await this.startLocalVideo();
        }
    }

    async startLocalVideo() {
        const localVideo = document.getElementById('local-video');
        if (!localVideo) return;

        try {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: {ideal: 1280},
                        height: {ideal: 720},
                        frameRate: {ideal: 30}
                    },
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                });
                localVideo.srcObject = stream;
                console.log('✅ Local video and audio started');

                // ✅ УБИРАЕМ демо-участников и загружаем реальных
                await this.loadRealParticipants();

            } else {
                console.warn('getUserMedia not supported');
                this.showMessage('Камера/микрофон не поддерживаются браузером', 'warning');
                await this.loadRealParticipants();
            }
        } catch (error) {
            console.warn('Cannot access camera/microphone:', error);
            this.showMessage('Не удалось получить доступ к камере/микрофону: ' + error.message, 'warning');
            await this.loadRealParticipants();
        }
    }

    async startVoximplantConference() {
        if (!this.voximplantClient || !this.isConnected) {
            console.warn('VoxImplant not connected, cannot start conference');
            return;
        }

        try {
            this.getCurrentUserID();
            console.log('🔍 Conference data check:', {
                currentRoom: this.currentRoom,
                currentUserID: this.currentUserID,
                roomId: this.currentRoom?.id,
                roomHash: this.currentRoom?.hash,
                room_id: this.currentRoom?.room_id
            });

            if (!this.currentRoom || !this.currentUserID) {
                throw new Error('Room or user data not available');
            }

            const roomId = this.currentRoom.id || this.currentRoom.room_id || 'unknown';
            const roomName = `Conference_${this.currentRoom.room_id}`;
            const userName = this.getUserName();

            console.log('🎯 Starting conference with:', {
                roomId: roomId,
                roomName: roomName,
                userName: userName,
                userId: this.currentUserID
            });

            const conferenceNumber = "video-conference";

            // ✅ ПРАВИЛЬНЫЕ НАСТРОЙКИ ДЛЯ VOXIMPLANT 4.6.0+
            const callSettings = {
                // ✅ НАСТРОЙКИ ВИДЕО (новый формат)
                video: true, // или { sendVideo: true, receiveVideo: true }

                // ✅ НАСТРОЙКИ АУДИО
                audio: true,

                // ✅ CUSTOM DATA (новый формат)
                customData: {
                    conference_name: roomName,
                    user_name: userName,
                    room_id: roomId.toString(),
                    user_id: this.currentUserID.toString(),
                    max_participants: '10'
                },

                // ✅ EXTRA HEADERS (новый формат)
                extraHeaders: {
                    'X-Conference-Name': roomName,
                    'X-User-Name': userName,
                    'X-Room-ID': roomId.toString(),
                    'X-User-ID': this.currentUserID.toString()
                }
            };

            console.log('📞 Conference call settings (4.6.0+):', callSettings);

            // ✅ ПРАВИЛЬНЫЙ ВЫЗОВ КОНФЕРЕНЦИИ
            const call = this.voximplantClient.callConference(conferenceNumber, callSettings);
            this.currentCall = call;

            // Обработчики событий
            call.on(VoxImplant.CallEvents.Connected, (e) => {
                console.log('✅ Connected to video-conference');
                this.showSuccess('Подключено к видеоконференции');

                // ✅ ПОЛУЧАЕМ CUSTOM DATA ИЗ ОТВЕТА
                console.log('📋 Call customData:', e.customData);
            });

            call.on(VoxImplant.CallEvents.Failed, (e) => {
                console.error('❌ Conference call failed:', e);
                if (e.code === 1004) {
                    this.showError('Сценарий video-conference не найден');
                } else {
                    this.showError(`Ошибка: ${e.reason} (код: ${e.code})`);
                }
                this.currentCall = null;
            });

            call.on(VoxImplant.CallEvents.Disconnected, (e) => {
                console.log('🔌 Disconnected from conference');
                this.currentCall = null;
                this.showMessage('Соединение с конференцией разорвано', 'warning');
            });

            // ✅ ОБРАБОТКА СОБЫТИЙ С CUSTOM DATA
            call.on(VoxImplant.CallEvents.MessageReceived, (e) => {
                console.log('📨 Conference message with customData:', e);
                if (e.customData) {
                    this.handleConferenceMessage(e.customData);
                }
            });

            // Обработка видеопотоков
            call.on(VoxImplant.CallEvents.RemoteVideoStreamAdded, (e) => {
                console.log('🎥 Remote video stream added:', e);
                this.displayRealVideoStream(e.userId, e.stream);
            });

            call.on(VoxImplant.CallEvents.RemoteVideoStreamRemoved, (e) => {
                console.log('🚫 Remote video stream removed:', e);
                this.removeParticipantVideo(e.userId);
            });

        } catch (error) {
            console.error('❌ Failed to start conference:', error);
            this.showError('Не удалось запустить конференцию: ' + error.message);
        }
    }

    // Удаление видео участника
    removeParticipantVideo(userId) {
        const participantElement = document.getElementById(`participant-${userId}`);
        if (participantElement) {
            participantElement.remove();
        }
    }

    getUserName() {
        // Здесь можно получить имя пользователя из Bitrix
        // Пока используем ID или стандартное имя
        return `User_${this.currentUserID}`;
    }

    displayRealVideoStream(userId, stream) {
        console.log('🎬 Displaying real video stream for user:', userId);

        const remoteVideos = document.getElementById('remote-videos');
        if (!remoteVideos) {
            console.error('❌ remote-videos container not found');
            return;
        }

        let videoContainer = document.getElementById(`participant-${userId}`);

        if (!videoContainer) {
            // Создаем новый контейнер для участника
            videoContainer = document.createElement('div');
            videoContainer.id = `participant-${userId}`;
            videoContainer.className = 'remote-video-container';
            videoContainer.innerHTML = `
            <video autoplay playsinline></video>
            <div class="participant-info">
                Участник ${userId}
            </div>
        `;
            remoteVideos.appendChild(videoContainer);
        }

        // Устанавливаем видеопоток
        const videoElement = videoContainer.querySelector('video');
        if (videoElement && stream) {
            videoElement.srcObject = stream;
            console.log('✅ Video stream set for participant:', userId);
        }
    }

    setupVoximplantEventHandlers() {
        if (!this.voximplantClient) return;

        // Обработчики событий подключения
        this.voximplantClient.on(VoxImplant.Events.ConnectionEstablished, () => {
            console.log('✅ Connection to VoxImplant established');
            this.isConnected = true;
            this.showSuccess('Подключено к VoxImplant Cloud');

            // Показываем интерфейс конференции после успешного подключения
            this.showConferenceInterface();

            // Запускаем локальное видео
            this.startLocalVideo();

            // Загружаем реальных участников из БД
            this.loadRealParticipants();

            // Запускаем реальную видеоконференцию
            this.startVoximplantConference();
        });

        this.voximplantClient.on(VoxImplant.Events.ConnectionClosed, () => {
            console.log('🔌 Connection to VoxImplant closed');
            this.isConnected = false;
            this.showMessage('Соединение с VoxImplant разорвано', 'warning');
        });

        this.voximplantClient.on(VoxImplant.Events.ConnectionFailed, (e) => {
            console.error('❌ Connection to VoxImplant failed:', e);
            this.isConnected = false;
            this.showError('Не удалось подключиться к VoxImplant');

            // Fallback: показываем интерфейс без Voximplant
            this.showConferenceInterface();
            this.startLocalVideo();
            this.showMessage('Используется локальное видео (Voximplant недоступен)', 'warning');
        });

        this.voximplantClient.on(VoxImplant.Events.AuthResult, (e) => {
            console.log('Auth result:', e);
            if (e.result) {
                console.log('✅ Authenticated successfully');
            } else {
                console.error('❌ Authentication failed');
            }
        });
    }

    async loadRealParticipants() {
        if (!this.currentRoom) {
            console.error('❌ Cannot load participants: currentRoom is undefined');
            this.displayNoParticipants();
            return;
        }

        const roomHash = this.currentRoom.hash || this.currentRoom.UF_HASH;
        if (!roomHash) {
            console.error('❌ Cannot load participants: room hash is undefined');
            this.displayNoParticipants();
            return;
        }

        try {
            console.log('🔄 Loading participants for room:', roomHash);

            const result = await BX.ajax.runComponentAction('xillix:chatvideo.conference', 'getRoomInfo', {
                mode: 'class',
                data: {
                    roomHash: roomHash
                }
            });

            console.log('✅ Room info response:', result.data);

            if (result.data && result.data.success) {
                console.log('📊 Participants data:', result.data.participants);
                this.displayRealParticipants(result.data.participants);
            } else {
                console.warn('❌ Failed to load room participants:', result.data?.error);
                this.displayNoParticipants();
            }
        } catch (error) {
            console.error('❌ Error loading participants:', error);
            this.displayNoParticipants();
        }
    }

    displayRealParticipants(participants) {
        const remoteVideos = document.getElementById('remote-videos');
        if (!remoteVideos) {
            console.error('❌ remote-videos container not found');
            return;
        }

        // Очищаем контейнер
        remoteVideos.innerHTML = '';

        console.log('🎯 Displaying participants:', participants);

        if (!participants || participants.length === 0) {
            console.log('ℹ️ No participants found');
            this.displayWaitingForParticipants();
            return;
        }

        // Фильтруем текущего пользователя из списка участников
        const otherParticipants = participants.filter(participant => {
            const participantUserId = participant.UF_USER_ID || participant.ID;
            return participantUserId !== this.currentUserID;
        });

        console.log('👥 Other participants after filtering:', otherParticipants);

        if (otherParticipants.length === 0) {
            console.log('ℹ️ No other participants found');
            this.displayWaitingForParticipants();
            return;
        }

        // Создаем элементы для реальных участников
        otherParticipants.forEach((participant, index) => {
            const participantUserId = participant.UF_USER_ID || participant.ID;
            console.log(`🎬 Creating video container for participant ${participantUserId}`);

            const videoContainer = document.createElement('div');
            videoContainer.className = 'remote-video-container';
            videoContainer.id = `participant-${participantUserId}`;

            videoContainer.innerHTML = `
            <div class="participant-video-placeholder">
                <div class="participant-avatar">
                    ${this.getUserInitials(participantUserId)}
                </div>
                <div class="participant-status">Участник ${index + 1}</div>
            </div>
            <div class="participant-info">
                ID: ${participantUserId}
            </div>
        `;

            remoteVideos.appendChild(videoContainer);
        });

        this.updateRoomInfo(otherParticipants.length + 1);
    }

    displayWaitingForParticipants() {
        const remoteVideos = document.getElementById('remote-videos');
        if (!remoteVideos) return;

        remoteVideos.innerHTML = `
        <div class="waiting-participants">
            <div class="waiting-icon">👥</div>
            <div class="waiting-text">Ожидание других участников...</div>
            <div class="waiting-hint">Поделитесь ссылкой на комнату</div>
        </div>
    `;
    }

    displayNoParticipants() {
        const remoteVideos = document.getElementById('remote-videos');
        if (!remoteVideos) return;

        remoteVideos.innerHTML = `
        <div class="no-participants">
            <div class="no-participants-icon">📹</div>
            <div class="no-participants-text">Участники не загружены</div>
        </div>
    `;
    }

    getUserInitials(userId) {
        return `U${userId}`;
    }

    updateRoomInfo(participantCount = 1) {
        const roomInfo = document.getElementById('room-info');
        if (roomInfo && this.currentRoom) {
            const voxStatus = this.isConnected ? '✅ Подключен' : '❌ Не подключен';
            roomInfo.innerHTML = `
                <div><strong>Комната:</strong> ${this.currentRoom.name || 'Без названия'}</div>
                <div><strong>Участников:</strong> ${participantCount}/10</div>
                <div><strong>VoxImplant:</strong> ${voxStatus}</div>
                <div><strong>Режим:</strong> ${this.isConnected ? 'VoxImplant' : 'Локальное видео'}</div>
            `;
        }
    }

    stopLocalVideo() {
        const localVideo = document.getElementById('local-video');
        if (localVideo && localVideo.srcObject) {
            const tracks = localVideo.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            localVideo.srcObject = null;
        }
    }

    generateRoomLink() {
        if (!this.currentRoom) return;

        const roomHash = this.currentRoom.hash || this.currentRoom.UF_HASH;
        if (!roomHash) return;

        const roomLink = `${window.location.origin}${window.location.pathname}?room=${roomHash}`;
        const roomLinkInput = document.getElementById('room-link');
        const roomLinkContainer = document.getElementById('room-link-container');

        if (roomLinkInput) {
            roomLinkInput.value = roomLink;
        }

        if (roomLinkContainer) {
            roomLinkContainer.style.display = 'block';
        }
    }

    showConferenceInterface() {
        const conferenceInterface = document.getElementById('conference-interface');
        const roomCreation = document.getElementById('room-creation');

        if (conferenceInterface) {
            conferenceInterface.style.display = 'block';
        }

        if (roomCreation) {
            roomCreation.style.display = 'none';
        }
    }

    showRoomCreation() {
        const conferenceInterface = document.getElementById('conference-interface');
        const roomCreation = document.getElementById('room-creation');
        const roomLinkContainer = document.getElementById('room-link-container');

        if (conferenceInterface) {
            conferenceInterface.style.display = 'none';
        }

        if (roomCreation) {
            roomCreation.style.display = 'block';
        }

        if (roomLinkContainer) {
            roomLinkContainer.style.display = 'none';
        }
    }

    toggleAudio() {
        const localVideo = document.getElementById('local-video');
        if (localVideo && localVideo.srcObject) {
            const audioTracks = localVideo.srcObject.getAudioTracks();
            if (audioTracks.length > 0) {
                const enabled = !audioTracks[0].enabled;
                audioTracks[0].enabled = enabled;
                this.showMessage(enabled ? '🎤 Аудио включено' : '🔇 Аудио выключено');
            }
        }
    }

    toggleVideo() {
        const localVideo = document.getElementById('local-video');
        if (localVideo && localVideo.srcObject) {
            const videoTracks = localVideo.srcObject.getVideoTracks();
            if (videoTracks.length > 0) {
                const enabled = !videoTracks[0].enabled;
                videoTracks[0].enabled = enabled;
                this.showMessage(enabled ? '📹 Видео включено' : '📷 Видео выключено');
            }
        }
    }

    showError(message) {
        this.showMessage('❌ ' + message, 'error');
    }

    showSuccess(message) {
        this.showMessage('✅ ' + message, 'success');
    }

    showMessage(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            z-index: 10000;
            font-family: Arial, sans-serif;
            font-size: 14px;
            max-width: 400px;
            word-wrap: break-word;
            background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : '#007bff'};
            color: ${type === 'warning' ? '#212529' : 'white'};
            border: 1px solid ${type === 'error' ? '#c82333' : type === 'success' ? '#1e7e34' : type === 'warning' ? '#e0a800' : '#0069d9'};
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Инициализация компонента при загрузке страницы
BX.ready(function () {
    const container = document.getElementById('chat-video-conference');
    if (!container) {
        console.error('Chat video conference container not found');
        return;
    }

    const signedParameters = container.getAttribute('data-signed-parameters');
    const voximplantAccountId = container.getAttribute('data-voximplant-account-id');
    const voximplantAppId = container.getAttribute('data-voximplant-app-id');

    console.log('Initializing ChatVideoConference with:', {
        hasSignedParameters: !!signedParameters,
        voximplantAccountId: voximplantAccountId,
        voximplantAppId: voximplantAppId
    });

    window.chatVideoConference = new ChatVideoConference({
        signedParameters: signedParameters,
        voximplantAccountId: voximplantAccountId,
        voximplantAppId: voximplantAppId
    });
});