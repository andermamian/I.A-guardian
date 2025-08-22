package com.guardianai.activities

import android.os.Bundle
import android.widget.*
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.RecyclerView
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.*
import com.guardianai.managers.*
import com.guardianai.adapters.*
import com.guardianai.models.*

/**
 * Actividad del Sistema Anti-Robo Guardian
 * Trabaja con Guardian Anti-Theft Sisten.xml (SIN MODIFICAR EL LAYOUT)
 * Implementa sistema avanzado de protección anti-robo con IA y biometría
 */
class GuardianAntiTheftActivity : AppCompatActivity() {
    
    // Managers especializados
    private lateinit var antiTheftManager: AntiTheftManager
    private lateinit var biometricSecurityManager: BiometricSecurityManager
    private lateinit var deviceTrackingManager: DeviceTrackingManager
    private lateinit var intrusionDetectionManager: IntrusionDetectionManager
    private lateinit var emergencyResponseManager: EmergencyResponseManager
    private lateinit var forensicAnalysisManager: ForensicAnalysisManager
    
    // UI Components (basados en tu layout)
    private lateinit var tvProtectionStatus: TextView
    private lateinit var tvDeviceLocation: TextView
    private lateinit var tvLastKnownLocation: TextView
    private lateinit var tvSecurityLevel: TextView
    private lateinit var tvIntrusionAttempts: TextView
    private lateinit var tvBiometricStatus: TextView
    private lateinit var tvEmergencyContacts: TextView
    private lateinit var progressProtectionLevel: ProgressBar
    private lateinit var progressLocationAccuracy: ProgressBar
    private lateinit var progressBiometricSecurity: ProgressBar
    private lateinit var btnActivateProtection: Button
    private lateinit var btnLocateDevice: Button
    private lateinit var btnRemoteWipe: Button
    private lateinit var btnEmergencyAlert: Button
    private lateinit var btnLockDevice: Button
    private lateinit var btnTakePhoto: Button
    private lateinit var btnRecordAudio: Button
    private lateinit var switchSilentMode: Switch
    private lateinit var switchBiometricLock: Switch
    private lateinit var switchLocationTracking: Switch
    private lateinit var switchIntrusionDetection: Switch
    private lateinit var switchAutoResponse: Switch
    private lateinit var spinnerSecurityLevel: Spinner
    private lateinit var spinnerResponseMode: Spinner
    private lateinit var rvSecurityEvents: RecyclerView
    private lateinit var rvLocationHistory: RecyclerView
    private lateinit var rvIntrusionAttempts: RecyclerView
    private lateinit var rvForensicData: RecyclerView
    private lateinit var mapDeviceLocation: ImageView
    
    // Adapters
    private lateinit var securityEventsAdapter: SecurityEventsAdapter
    private lateinit var locationHistoryAdapter: LocationHistoryAdapter
    private lateinit var intrusionAttemptsAdapter: IntrusionAttemptsAdapter
    private lateinit var forensicDataAdapter: ForensicDataAdapter
    
    // Estados del sistema anti-robo
    private var isProtectionActive = false
    private var currentSecurityLevel = AntiTheftSecurityLevel.HIGH
    private var isLocationTrackingActive = true
    private var isBiometricLockActive = true
    private var isIntrusionDetectionActive = true
    private var currentLocation: DeviceLocation? = null
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.guardian_anti_theft_sisten) // TU LAYOUT ORIGINAL
        
        initializeComponents()
        initializeManagers()
        setupAdapters()
        setupEventListeners()
        startAntiTheftSystem()
    }
    
    private fun initializeComponents() {
        // Mapear componentes de tu layout original
        tvProtectionStatus = findViewById(R.id.tv_protection_status)
        tvDeviceLocation = findViewById(R.id.tv_device_location)
        tvLastKnownLocation = findViewById(R.id.tv_last_known_location)
        tvSecurityLevel = findViewById(R.id.tv_security_level)
        tvIntrusionAttempts = findViewById(R.id.tv_intrusion_attempts)
        tvBiometricStatus = findViewById(R.id.tv_biometric_status)
        tvEmergencyContacts = findViewById(R.id.tv_emergency_contacts)
        progressProtectionLevel = findViewById(R.id.progress_protection_level)
        progressLocationAccuracy = findViewById(R.id.progress_location_accuracy)
        progressBiometricSecurity = findViewById(R.id.progress_biometric_security)
        btnActivateProtection = findViewById(R.id.btn_activate_protection)
        btnLocateDevice = findViewById(R.id.btn_locate_device)
        btnRemoteWipe = findViewById(R.id.btn_remote_wipe)
        btnEmergencyAlert = findViewById(R.id.btn_emergency_alert)
        btnLockDevice = findViewById(R.id.btn_lock_device)
        btnTakePhoto = findViewById(R.id.btn_take_photo)
        btnRecordAudio = findViewById(R.id.btn_record_audio)
        switchSilentMode = findViewById(R.id.switch_silent_mode)
        switchBiometricLock = findViewById(R.id.switch_biometric_lock)
        switchLocationTracking = findViewById(R.id.switch_location_tracking)
        switchIntrusionDetection = findViewById(R.id.switch_intrusion_detection)
        switchAutoResponse = findViewById(R.id.switch_auto_response)
        spinnerSecurityLevel = findViewById(R.id.spinner_security_level)
        spinnerResponseMode = findViewById(R.id.spinner_response_mode)
        rvSecurityEvents = findViewById(R.id.rv_security_events)
        rvLocationHistory = findViewById(R.id.rv_location_history)
        rvIntrusionAttempts = findViewById(R.id.rv_intrusion_attempts)
        rvForensicData = findViewById(R.id.rv_forensic_data)
        mapDeviceLocation = findViewById(R.id.map_device_location)
        
        // Configurar estado inicial
        updateProtectionStatus(false)
        updateSecurityLevel(AntiTheftSecurityLevel.HIGH)
        setupSpinners()
    }
    
    private fun initializeManagers() {
        antiTheftManager = AntiTheftManager(this)
        biometricSecurityManager = BiometricSecurityManager(this)
        deviceTrackingManager = DeviceTrackingManager(this)
        intrusionDetectionManager = IntrusionDetectionManager(this)
        emergencyResponseManager = EmergencyResponseManager(this)
        forensicAnalysisManager = ForensicAnalysisManager(this)
    }
    
    private fun setupAdapters() {
        securityEventsAdapter = SecurityEventsAdapter { event ->
            onSecurityEventClicked(event)
        }
        rvSecurityEvents.adapter = securityEventsAdapter
        
        locationHistoryAdapter = LocationHistoryAdapter { location ->
            onLocationHistoryClicked(location)
        }
        rvLocationHistory.adapter = locationHistoryAdapter
        
        intrusionAttemptsAdapter = IntrusionAttemptsAdapter { attempt ->
            onIntrusionAttemptClicked(attempt)
        }
        rvIntrusionAttempts.adapter = intrusionAttemptsAdapter
        
        forensicDataAdapter = ForensicDataAdapter { data ->
            onForensicDataClicked(data)
        }
        rvForensicData.adapter = forensicDataAdapter
    }
    
    private fun setupEventListeners() {
        // Botón activar protección
        btnActivateProtection.setOnClickListener {
            toggleProtection()
        }
        
        // Botón localizar dispositivo
        btnLocateDevice.setOnClickListener {
            locateDevice()
        }
        
        // Botón borrado remoto
        btnRemoteWipe.setOnClickListener {
            performRemoteWipe()
        }
        
        // Botón alerta de emergencia
        btnEmergencyAlert.setOnClickListener {
            triggerEmergencyAlert()
        }
        
        // Botón bloquear dispositivo
        btnLockDevice.setOnClickListener {
            lockDeviceRemotely()
        }
        
        // Botón tomar foto
        btnTakePhoto.setOnClickListener {
            takeStealthPhoto()
        }
        
        // Botón grabar audio
        btnRecordAudio.setOnClickListener {
            recordStealthAudio()
        }
        
        // Switches
        switchSilentMode.setOnCheckedChangeListener { _, isChecked ->
            toggleSilentMode(isChecked)
        }
        
        switchBiometricLock.setOnCheckedChangeListener { _, isChecked ->
            toggleBiometricLock(isChecked)
        }
        
        switchLocationTracking.setOnCheckedChangeListener { _, isChecked ->
            toggleLocationTracking(isChecked)
        }
        
        switchIntrusionDetection.setOnCheckedChangeListener { _, isChecked ->
            toggleIntrusionDetection(isChecked)
        }
        
        switchAutoResponse.setOnCheckedChangeListener { _, isChecked ->
            toggleAutoResponse(isChecked)
        }
        
        // Spinners
        spinnerSecurityLevel.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, view: android.view.View?, position: Int, id: Long) {
                val selectedLevel = AntiTheftSecurityLevel.values()[position]
                onSecurityLevelSelected(selectedLevel)
            }
            override fun onNothingSelected(parent: AdapterView<*>?) {}
        }
        
        spinnerResponseMode.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, view: android.view.View?, position: Int, id: Long) {
                val selectedMode = ResponseMode.values()[position]
                onResponseModeSelected(selectedMode)
            }
            override fun onNothingSelected(parent: AdapterView<*>?) {}
        }
        
        // Mapa de ubicación
        mapDeviceLocation.setOnClickListener {
            showDetailedLocationMap()
        }
    }
    
    private fun startAntiTheftSystem() {
        lifecycleScope.launch {
            // Inicializar sistemas anti-robo
            antiTheftManager.initialize()
            biometricSecurityManager.initialize()
            deviceTrackingManager.initialize()
            intrusionDetectionManager.initialize()
            emergencyResponseManager.initialize()
            forensicAnalysisManager.initialize()
            
            // Cargar configuración inicial
            loadAntiTheftConfiguration()
            
            // Iniciar monitoreo
            launch { monitorDeviceLocation() }
            launch { monitorIntrusionAttempts() }
            launch { monitorBiometricSecurity() }
            launch { monitorSecurityEvents() }
            
            // Verificar estado inicial
            checkInitialSecurityStatus()
        }
    }
    
    private suspend fun monitorDeviceLocation() {
        if (isLocationTrackingActive) {
            deviceTrackingManager.locationUpdates.collect { location ->
                runOnUiThread {
                    updateDeviceLocationUI(location)
                }
            }
        }
    }
    
    private suspend fun monitorIntrusionAttempts() {
        if (isIntrusionDetectionActive) {
            intrusionDetectionManager.intrusionAttempts.collect { attempt ->
                runOnUiThread {
                    handleIntrusionAttempt(attempt)
                }
            }
        }
    }
    
    private suspend fun monitorBiometricSecurity() {
        if (isBiometricLockActive) {
            biometricSecurityManager.biometricEvents.collect { event ->
                runOnUiThread {
                    handleBiometricEvent(event)
                }
            }
        }
    }
    
    private suspend fun monitorSecurityEvents() {
        antiTheftManager.securityEvents.collect { events ->
            runOnUiThread {
                updateSecurityEventsUI(events)
            }
        }
    }
    
    private fun setupSpinners() {
        // Configurar spinner de nivel de seguridad
        val securityLevels = AntiTheftSecurityLevel.values().map { it.displayName }
        val securityAdapter = ArrayAdapter(this, android.R.layout.simple_spinner_item, securityLevels)
        securityAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        spinnerSecurityLevel.adapter = securityAdapter
        
        // Configurar spinner de modo de respuesta
        val responseModes = ResponseMode.values().map { it.displayName }
        val responseAdapter = ArrayAdapter(this, android.R.layout.simple_spinner_item, responseModes)
        responseAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        spinnerResponseMode.adapter = responseAdapter
    }
    
    private fun toggleProtection() {
        lifecycleScope.launch {
            if (isProtectionActive) {
                deactivateProtection()
            } else {
                activateProtection()
            }
        }
    }
    
    private suspend fun activateProtection() {
        try {
            btnActivateProtection.isEnabled = false
            btnActivateProtection.text = "Activando..."
            
            // Activar todos los sistemas de protección
            val activationResult = antiTheftManager.activateProtection(
                securityLevel = currentSecurityLevel,
                enableBiometric = isBiometricLockActive,
                enableLocationTracking = isLocationTrackingActive,
                enableIntrusionDetection = isIntrusionDetectionActive
            )
            
            if (activationResult.success) {
                isProtectionActive = true
                updateProtectionStatus(true)
                
                // Iniciar monitoreo activo
                startActiveMonitoring()
                
                showToast("Protección anti-robo activada")
            } else {
                showToast("Error activando protección: ${activationResult.error}")
            }
            
        } catch (e: Exception) {
            showToast("Error: ${e.message}")
        } finally {
            btnActivateProtection.isEnabled = true
            updateActivationButtonText()
        }
    }
    
    private suspend fun deactivateProtection() {
        showDeactivationConfirmationDialog { confirmed ->
            if (confirmed) {
                lifecycleScope.launch {
                    try {
                        antiTheftManager.deactivateProtection()
                        isProtectionActive = false
                        updateProtectionStatus(false)
                        
                        stopActiveMonitoring()
                        
                        showToast("Protección anti-robo desactivada")
                        
                    } catch (e: Exception) {
                        showToast("Error desactivando protección: ${e.message}")
                    } finally {
                        updateActivationButtonText()
                    }
                }
            }
        }
    }
    
    private fun locateDevice() {
        lifecycleScope.launch {
            try {
                btnLocateDevice.isEnabled = false
                btnLocateDevice.text = "Localizando..."
                
                // Obtener ubicación actual del dispositivo
                val location = deviceTrackingManager.getCurrentLocation()
                
                if (location != null) {
                    currentLocation = location
                    updateDeviceLocationUI(location)
                    
                    // Mostrar en mapa
                    showLocationOnMap(location)
                    
                    // Guardar en historial
                    locationHistoryAdapter.addLocation(location)
                    
                    showToast("Dispositivo localizado exitosamente")
                } else {
                    showToast("No se pudo obtener la ubicación del dispositivo")
                }
                
            } catch (e: Exception) {
                showToast("Error localizando dispositivo: ${e.message}")
            } finally {
                btnLocateDevice.isEnabled = true
                btnLocateDevice.text = "Localizar Dispositivo"
            }
        }
    }
    
    private fun performRemoteWipe() {
        lifecycleScope.launch {
            showRemoteWipeConfirmationDialog { confirmed ->
                if (confirmed) {
                    lifecycleScope.launch {
                        try {
                            btnRemoteWipe.isEnabled = false
                            btnRemoteWipe.text = "Borrando..."
                            
                            // Realizar borrado remoto
                            val wipeResult = antiTheftManager.performRemoteWipe()
                            
                            if (wipeResult.success) {
                                showToast("Borrado remoto iniciado exitosamente")
                                
                                // Registrar evento forense
                                forensicAnalysisManager.recordForensicEvent(
                                    ForensicEvent.REMOTE_WIPE_INITIATED,
                                    "Borrado remoto iniciado por el usuario"
                                )
                            } else {
                                showToast("Error en borrado remoto: ${wipeResult.error}")
                            }
                            
                        } catch (e: Exception) {
                            showToast("Error: ${e.message}")
                        } finally {
                            btnRemoteWipe.isEnabled = true
                            btnRemoteWipe.text = "Borrado Remoto"
                        }
                    }
                }
            }
        }
    }
    
    private fun triggerEmergencyAlert() {
        lifecycleScope.launch {
            try {
                btnEmergencyAlert.isEnabled = false
                btnEmergencyAlert.text = "Enviando Alerta..."
                
                // Activar alerta de emergencia
                val alertResult = emergencyResponseManager.triggerEmergencyAlert(
                    location = currentLocation,
                    reason = "Activación manual de alerta anti-robo"
                )
                
                if (alertResult.success) {
                    showToast("Alerta de emergencia enviada")
                    
                    // Activar todas las medidas de emergencia
                    activateEmergencyMeasures()
                } else {
                    showToast("Error enviando alerta: ${alertResult.error}")
                }
                
            } catch (e: Exception) {
                showToast("Error: ${e.message}")
            } finally {
                btnEmergencyAlert.isEnabled = true
                btnEmergencyAlert.text = "Alerta de Emergencia"
            }
        }
    }
    
    private fun lockDeviceRemotely() {
        lifecycleScope.launch {
            try {
                btnLockDevice.isEnabled = false
                btnLockDevice.text = "Bloqueando..."
                
                // Bloquear dispositivo remotamente
                val lockResult = antiTheftManager.lockDeviceRemotely()
                
                if (lockResult.success) {
                    showToast("Dispositivo bloqueado remotamente")
                } else {
                    showToast("Error bloqueando dispositivo: ${lockResult.error}")
                }
                
            } catch (e: Exception) {
                showToast("Error: ${e.message}")
            } finally {
                btnLockDevice.isEnabled = true
                btnLockDevice.text = "Bloquear Dispositivo"
            }
        }
    }
    
    private fun takeStealthPhoto() {
        lifecycleScope.launch {
            try {
                btnTakePhoto.isEnabled = false
                btnTakePhoto.text = "Capturando..."
                
                // Tomar foto sigilosa
                val photoResult = forensicAnalysisManager.takeStealthPhoto()
                
                if (photoResult.success) {
                    showToast("Foto capturada sigilosamente")
                    
                    // Agregar a datos forenses
                    forensicDataAdapter.addForensicData(
                        ForensicData(
                            type = ForensicDataType.PHOTO,
                            timestamp = System.currentTimeMillis(),
                            description = "Foto sigilosa capturada",
                            filePath = photoResult.filePath
                        )
                    )
                } else {
                    showToast("Error capturando foto: ${photoResult.error}")
                }
                
            } catch (e: Exception) {
                showToast("Error: ${e.message}")
            } finally {
                btnTakePhoto.isEnabled = true
                btnTakePhoto.text = "Tomar Foto"
            }
        }
    }
    
    private fun recordStealthAudio() {
        lifecycleScope.launch {
            try {
                btnRecordAudio.isEnabled = false
                btnRecordAudio.text = "Grabando..."
                
                // Grabar audio sigiloso
                val audioResult = forensicAnalysisManager.recordStealthAudio(duration = 30) // 30 segundos
                
                if (audioResult.success) {
                    showToast("Audio grabado sigilosamente")
                    
                    // Agregar a datos forenses
                    forensicDataAdapter.addForensicData(
                        ForensicData(
                            type = ForensicDataType.AUDIO,
                            timestamp = System.currentTimeMillis(),
                            description = "Audio sigiloso grabado (30s)",
                            filePath = audioResult.filePath
                        )
                    )
                } else {
                    showToast("Error grabando audio: ${audioResult.error}")
                }
                
            } catch (e: Exception) {
                showToast("Error: ${e.message}")
            } finally {
                btnRecordAudio.isEnabled = true
                btnRecordAudio.text = "Grabar Audio"
            }
        }
    }
    
    private fun toggleSilentMode(enabled: Boolean) {
        lifecycleScope.launch {
            if (enabled) {
                antiTheftManager.enableSilentMode()
                showToast("Modo silencioso activado")
            } else {
                antiTheftManager.disableSilentMode()
                showToast("Modo silencioso desactivado")
            }
        }
    }
    
    private fun toggleBiometricLock(enabled: Boolean) {
        isBiometricLockActive = enabled
        
        lifecycleScope.launch {
            if (enabled) {
                biometricSecurityManager.enableBiometricLock()
                launch { monitorBiometricSecurity() }
                showToast("Bloqueo biométrico activado")
            } else {
                biometricSecurityManager.disableBiometricLock()
                showToast("Bloqueo biométrico desactivado")
            }
            
            updateBiometricStatusUI()
        }
    }
    
    private fun toggleLocationTracking(enabled: Boolean) {
        isLocationTrackingActive = enabled
        
        lifecycleScope.launch {
            if (enabled) {
                deviceTrackingManager.enableLocationTracking()
                launch { monitorDeviceLocation() }
                showToast("Rastreo de ubicación activado")
            } else {
                deviceTrackingManager.disableLocationTracking()
                showToast("Rastreo de ubicación desactivado")
            }
        }
    }
    
    private fun toggleIntrusionDetection(enabled: Boolean) {
        isIntrusionDetectionActive = enabled
        
        lifecycleScope.launch {
            if (enabled) {
                intrusionDetectionManager.enableIntrusionDetection()
                launch { monitorIntrusionAttempts() }
                showToast("Detección de intrusión activada")
            } else {
                intrusionDetectionManager.disableIntrusionDetection()
                showToast("Detección de intrusión desactivada")
            }
        }
    }
    
    private fun toggleAutoResponse(enabled: Boolean) {
        lifecycleScope.launch {
            if (enabled) {
                emergencyResponseManager.enableAutoResponse()
                showToast("Respuesta automática activada")
            } else {
                emergencyResponseManager.disableAutoResponse()
                showToast("Respuesta automática desactivada")
            }
        }
    }
    
    private fun onSecurityLevelSelected(level: AntiTheftSecurityLevel) {
        currentSecurityLevel = level
        updateSecurityLevel(level)
        
        lifecycleScope.launch {
            antiTheftManager.setSecurityLevel(level)
        }
    }
    
    private fun onResponseModeSelected(mode: ResponseMode) {
        lifecycleScope.launch {
            emergencyResponseManager.setResponseMode(mode)
            showToast("Modo de respuesta cambiado a: ${mode.displayName}")
        }
    }
    
    private fun showDetailedLocationMap() {
        if (currentLocation != null) {
            showLocationDetailsDialog(currentLocation!!)
        } else {
            showToast("No hay ubicación disponible")
        }
    }
    
    private fun updateProtectionStatus(active: Boolean) {
        isProtectionActive = active
        
        tvProtectionStatus.text = if (active) {
            "🛡️ PROTECCIÓN ACTIVA"
        } else {
            "⚠️ PROTECCIÓN INACTIVA"
        }
        
        val color = if (active) {
            getColor(android.R.color.holo_green_dark)
        } else {
            getColor(android.R.color.holo_red_dark)
        }
        
        tvProtectionStatus.setTextColor(color)
        
        // Actualizar nivel de protección
        val protectionLevel = if (active) {
            when (currentSecurityLevel) {
                AntiTheftSecurityLevel.LOW -> 40
                AntiTheftSecurityLevel.MEDIUM -> 60
                AntiTheftSecurityLevel.HIGH -> 80
                AntiTheftSecurityLevel.MAXIMUM -> 95
                AntiTheftSecurityLevel.STEALTH -> 100
            }
        } else {
            0
        }
        
        progressProtectionLevel.progress = protectionLevel
    }
    
    private fun updateSecurityLevel(level: AntiTheftSecurityLevel) {
        currentSecurityLevel = level
        
        tvSecurityLevel.text = when (level) {
            AntiTheftSecurityLevel.LOW -> "Nivel: Básico"
            AntiTheftSecurityLevel.MEDIUM -> "Nivel: Medio"
            AntiTheftSecurityLevel.HIGH -> "Nivel: Alto"
            AntiTheftSecurityLevel.MAXIMUM -> "Nivel: Máximo"
            AntiTheftSecurityLevel.STEALTH -> "Nivel: Sigiloso"
        }
    }
    
    private fun updateActivationButtonText() {
        btnActivateProtection.text = if (isProtectionActive) {
            "Desactivar Protección"
        } else {
            "Activar Protección"
        }
    }
    
    private fun updateDeviceLocationUI(location: DeviceLocation) {
        currentLocation = location
        
        tvDeviceLocation.text = "📍 ${location.address}"
        tvLastKnownLocation.text = "Última ubicación: ${java.text.SimpleDateFormat("HH:mm:ss").format(location.timestamp)}"
        
        // Actualizar precisión
        progressLocationAccuracy.progress = (location.accuracy * 100).toInt()
        
        // Actualizar mapa
        updateLocationMap(location)
    }
    
    private fun updateBiometricStatusUI() {
        val status = if (isBiometricLockActive) {
            "🔒 Biometría Activa"
        } else {
            "🔓 Biometría Inactiva"
        }
        
        tvBiometricStatus.text = status
        
        // Actualizar progreso de seguridad biométrica
        val securityLevel = if (isBiometricLockActive) 90 else 20
        progressBiometricSecurity.progress = securityLevel
    }
    
    private fun updateSecurityEventsUI(events: List<AntiTheftSecurityEvent>) {
        securityEventsAdapter.updateEvents(events)
    }
    
    private fun handleIntrusionAttempt(attempt: IntrusionAttempt) {
        // Agregar intento de intrusión a la lista
        intrusionAttemptsAdapter.addAttempt(attempt)
        
        // Actualizar contador
        val totalAttempts = intrusionAttemptsAdapter.itemCount
        tvIntrusionAttempts.text = "Intentos de intrusión: $totalAttempts"
        
        // Respuesta automática si está habilitada
        if (switchAutoResponse.isChecked) {
            lifecycleScope.launch {
                respondToIntrusionAttempt(attempt)
            }
        }
        
        // Mostrar notificación
        showIntrusionAttemptNotification(attempt)
    }
    
    private fun handleBiometricEvent(event: BiometricEvent) {
        when (event.type) {
            BiometricEventType.AUTHENTICATION_FAILED -> {
                // Registrar fallo de autenticación
                forensicAnalysisManager.recordForensicEvent(
                    ForensicEvent.BIOMETRIC_FAILURE,
                    "Fallo de autenticación biométrica: ${event.details}"
                )
            }
            BiometricEventType.MULTIPLE_FAILURES -> {
                // Múltiples fallos - posible intrusión
                triggerSecurityAlert("Múltiples fallos biométricos detectados")
            }
            BiometricEventType.UNKNOWN_BIOMETRIC -> {
                // Biometría desconocida - definitivamente intrusión
                triggerSecurityAlert("Biometría desconocida detectada")
                
                if (switchAutoResponse.isChecked) {
                    lifecycleScope.launch {
                        activateEmergencyMeasures()
                    }
                }
            }
        }
    }
    
    private suspend fun startActiveMonitoring() {
        // Iniciar todos los sistemas de monitoreo
        if (isLocationTrackingActive) {
            deviceTrackingManager.startActiveTracking()
        }
        
        if (isIntrusionDetectionActive) {
            intrusionDetectionManager.startActiveDetection()
        }
        
        if (isBiometricLockActive) {
            biometricSecurityManager.startActiveMonitoring()
        }
    }
    
    private suspend fun stopActiveMonitoring() {
        deviceTrackingManager.stopActiveTracking()
        intrusionDetectionManager.stopActiveDetection()
        biometricSecurityManager.stopActiveMonitoring()
    }
    
    private suspend fun activateEmergencyMeasures() {
        // Tomar foto sigilosa
        forensicAnalysisManager.takeStealthPhoto()
        
        // Grabar audio
        forensicAnalysisManager.recordStealthAudio(duration = 60)
        
        // Obtener ubicación
        val location = deviceTrackingManager.getCurrentLocation()
        
        // Enviar alerta de emergencia
        emergencyResponseManager.triggerEmergencyAlert(
            location = location,
            reason = "Medidas de emergencia activadas automáticamente"
        )
        
        // Bloquear dispositivo
        antiTheftManager.lockDeviceRemotely()
    }
    
    private suspend fun respondToIntrusionAttempt(attempt: IntrusionAttempt) {
        when (attempt.severity) {
            IntrusionSeverity.LOW -> {
                // Respuesta mínima
                forensicAnalysisManager.recordForensicEvent(
                    ForensicEvent.INTRUSION_ATTEMPT,
                    "Intento de intrusión de baja severidad"
                )
            }
            IntrusionSeverity.MEDIUM -> {
                // Respuesta moderada
                takeStealthPhoto()
                antiTheftManager.lockDeviceRemotely()
            }
            IntrusionSeverity.HIGH -> {
                // Respuesta completa
                activateEmergencyMeasures()
            }
        }
    }
    
    private fun triggerSecurityAlert(message: String) {
        lifecycleScope.launch {
            emergencyResponseManager.triggerSecurityAlert(message)
            showToast("Alerta de seguridad: $message")
        }
    }
    
    private suspend fun loadAntiTheftConfiguration() {
        val config = antiTheftManager.loadConfiguration()
        
        runOnUiThread {
            // Aplicar configuración cargada
            switchBiometricLock.isChecked = config.biometricLockEnabled
            switchLocationTracking.isChecked = config.locationTrackingEnabled
            switchIntrusionDetection.isChecked = config.intrusionDetectionEnabled
            switchAutoResponse.isChecked = config.autoResponseEnabled
            
            spinnerSecurityLevel.setSelection(config.securityLevel.ordinal)
            spinnerResponseMode.setSelection(config.responseMode.ordinal)
            
            // Actualizar estados
            isBiometricLockActive = config.biometricLockEnabled
            isLocationTrackingActive = config.locationTrackingEnabled
            isIntrusionDetectionActive = config.intrusionDetectionEnabled
            currentSecurityLevel = config.securityLevel
        }
    }
    
    private suspend fun checkInitialSecurityStatus() {
        // Verificar estado inicial del dispositivo
        val securityStatus = antiTheftManager.checkSecurityStatus()
        
        runOnUiThread {
            updateProtectionStatus(securityStatus.isProtectionActive)
            updateBiometricStatusUI()
            
            if (securityStatus.hasRecentThreats) {
                showToast("⚠️ Se detectaron amenazas recientes")
            }
        }
    }
    
    private fun updateLocationMap(location: DeviceLocation) {
        // Actualizar mapa con la ubicación
        // mapDeviceLocation.setImageBitmap(generateLocationMap(location))
    }
    
    private fun showLocationOnMap(location: DeviceLocation) {
        // Mostrar ubicación en mapa detallado
        updateLocationMap(location)
    }
    
    private fun showIntrusionAttemptNotification(attempt: IntrusionAttempt) {
        val message = "Intento de intrusión detectado: ${attempt.type}"
        showToast(message)
    }
    
    private fun onSecurityEventClicked(event: AntiTheftSecurityEvent) {
        showSecurityEventDetailsDialog(event)
    }
    
    private fun onLocationHistoryClicked(location: DeviceLocation) {
        showLocationDetailsDialog(location)
    }
    
    private fun onIntrusionAttemptClicked(attempt: IntrusionAttempt) {
        showIntrusionAttemptDetailsDialog(attempt)
    }
    
    private fun onForensicDataClicked(data: ForensicData) {
        showForensicDataDetailsDialog(data)
    }
    
    // Métodos de diálogos
    private fun showDeactivationConfirmationDialog(callback: (Boolean) -> Unit) {
        androidx.appcompat.app.AlertDialog.Builder(this)
            .setTitle("Desactivar Protección")
            .setMessage("¿Está seguro de que desea desactivar la protección anti-robo? Esto dejará su dispositivo vulnerable.")
            .setPositiveButton("Desactivar") { _, _ -> callback(true) }
            .setNegativeButton("Cancelar") { _, _ -> callback(false) }
            .show()
    }
    
    private fun showRemoteWipeConfirmationDialog(callback: (Boolean) -> Unit) {
        androidx.appcompat.app.AlertDialog.Builder(this)
            .setTitle("⚠️ BORRADO REMOTO")
            .setMessage("ADVERTENCIA: Esta acción borrará PERMANENTEMENTE todos los datos del dispositivo. Esta acción NO se puede deshacer.\n\n¿Está ABSOLUTAMENTE seguro?")
            .setPositiveButton("SÍ, BORRAR TODO") { _, _ -> callback(true) }
            .setNegativeButton("Cancelar") { _, _ -> callback(false) }
            .show()
    }
    
    private fun showLocationDetailsDialog(location: DeviceLocation) {
        androidx.appcompat.app.AlertDialog.Builder(this)
            .setTitle("📍 Detalles de Ubicación")
            .setMessage("""
                Dirección: ${location.address}
                Coordenadas: ${location.latitude}, ${location.longitude}
                Precisión: ${(location.accuracy * 100).toInt()}%
                Timestamp: ${java.text.SimpleDateFormat("dd/MM/yyyy HH:mm:ss").format(location.timestamp)}
                
                Método: ${location.method}
                Proveedor: ${location.provider}
            """.trimIndent())
            .setPositiveButton("Cerrar", null)
            .setNeutralButton("Ver en Mapa") { _, _ ->
                showLocationOnMap(location)
            }
            .show()
    }
    
    private fun showSecurityEventDetailsDialog(event: AntiTheftSecurityEvent) {
        androidx.appcompat.app.AlertDialog.Builder(this)
            .setTitle("🔒 Evento de Seguridad")
            .setMessage("""
                Tipo: ${event.type}
                Severidad: ${event.severity}
                Timestamp: ${java.text.SimpleDateFormat("dd/MM/yyyy HH:mm:ss").format(event.timestamp)}
                
                Descripción: ${event.description}
                
                Origen: ${event.source}
                Estado: ${event.status}
                
                ${if (event.actionTaken != null) "Acción tomada: ${event.actionTaken}" else ""}
            """.trimIndent())
            .setPositiveButton("Cerrar", null)
            .show()
    }
    
    private fun showIntrusionAttemptDetailsDialog(attempt: IntrusionAttempt) {
        androidx.appcompat.app.AlertDialog.Builder(this)
            .setTitle("🚨 Intento de Intrusión")
            .setMessage("""
                Tipo: ${attempt.type}
                Severidad: ${attempt.severity.name}
                Timestamp: ${java.text.SimpleDateFormat("dd/MM/yyyy HH:mm:ss").format(attempt.timestamp)}
                
                Método: ${attempt.method}
                Origen: ${attempt.source}
                
                Detalles: ${attempt.details}
                
                Respuesta: ${attempt.responseAction ?: "Ninguna"}
            """.trimIndent())
            .setPositiveButton("Cerrar", null)
            .show()
    }
    
    private fun showForensicDataDetailsDialog(data: ForensicData) {
        androidx.appcompat.app.AlertDialog.Builder(this)
            .setTitle("🔍 Datos Forenses")
            .setMessage("""
                Tipo: ${data.type.name}
                Timestamp: ${java.text.SimpleDateFormat("dd/MM/yyyy HH:mm:ss").format(data.timestamp)}
                
                Descripción: ${data.description}
                
                Archivo: ${data.filePath}
                Tamaño: ${data.fileSize ?: "N/A"}
                
                Hash: ${data.fileHash ?: "N/A"}
            """.trimIndent())
            .setPositiveButton("Cerrar", null)
            .setNeutralButton("Ver Archivo") { _, _ ->
                openForensicFile(data)
            }
            .show()
    }
    
    private fun openForensicFile(data: ForensicData) {
        // Implementar apertura de archivo forense
        showToast("Abriendo archivo forense: ${data.filePath}")
    }
    
    private fun showToast(message: String) {
        Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
    }
    
    // Enums y clases de datos
    enum class AntiTheftSecurityLevel(val displayName: String) {
        LOW("Básico"),
        MEDIUM("Medio"),
        HIGH("Alto"),
        MAXIMUM("Máximo"),
        STEALTH("Sigiloso")
    }
    
    enum class ResponseMode(val displayName: String) {
        PASSIVE("Pasivo"),
        ACTIVE("Activo"),
        AGGRESSIVE("Agresivo"),
        STEALTH("Sigiloso")
    }
    
    enum class IntrusionSeverity { LOW, MEDIUM, HIGH }
    enum class BiometricEventType { AUTHENTICATION_FAILED, MULTIPLE_FAILURES, UNKNOWN_BIOMETRIC }
    enum class ForensicEvent { INTRUSION_ATTEMPT, BIOMETRIC_FAILURE, REMOTE_WIPE_INITIATED, EMERGENCY_ACTIVATED }
    enum class ForensicDataType { PHOTO, AUDIO, VIDEO, LOG, SCREENSHOT }
    
    data class DeviceLocation(
        val latitude: Double,
        val longitude: Double,
        val address: String,
        val accuracy: Float,
        val timestamp: Long,
        val method: String,
        val provider: String
    )
    
    data class IntrusionAttempt(
        val type: String,
        val severity: IntrusionSeverity,
        val timestamp: Long,
        val method: String,
        val source: String,
        val details: String,
        val responseAction: String?
    )
    
    data class BiometricEvent(
        val type: BiometricEventType,
        val timestamp: Long,
        val details: String,
        val attemptCount: Int
    )
    
    data class AntiTheftSecurityEvent(
        val type: String,
        val severity: String,
        val timestamp: Long,
        val description: String,
        val source: String,
        val status: String,
        val actionTaken: String?
    )
    
    data class ForensicData(
        val type: ForensicDataType,
        val timestamp: Long,
        val description: String,
        val filePath: String,
        val fileSize: String? = null,
        val fileHash: String? = null
    )
    
    data class AntiTheftConfiguration(
        val biometricLockEnabled: Boolean,
        val locationTrackingEnabled: Boolean,
        val intrusionDetectionEnabled: Boolean,
        val autoResponseEnabled: Boolean,
        val securityLevel: AntiTheftSecurityLevel,
        val responseMode: ResponseMode
    )
    
    data class SecurityStatus(
        val isProtectionActive: Boolean,
        val hasRecentThreats: Boolean,
        val lastSecurityCheck: Long
    )
    
    data class ActivationResult(
        val success: Boolean,
        val error: String?
    )
    
    data class RemoteWipeResult(
        val success: Boolean,
        val error: String?
    )
    
    data class EmergencyAlertResult(
        val success: Boolean,
        val error: String?
    )
    
    data class StealthCaptureResult(
        val success: Boolean,
        val filePath: String?,
        val error: String?
    )
}

