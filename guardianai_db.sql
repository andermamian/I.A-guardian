-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 10-09-2025 a las 05:48:05
-- Versión del servidor: 8.0.17
-- Versión de PHP: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `guardianai_db`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanupOldData` ()  BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    
    DELETE FROM user_sessions WHERE expires_at < NOW();
    DELETE FROM conversation_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY);
    DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    DELETE FROM security_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY) AND resolved = TRUE;
    DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 DAY);
    DELETE FROM notifications WHERE expires_at < NOW();
    
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateComposition` (IN `p_user_id` INT, IN `p_composition_id` VARCHAR(50), IN `p_title` VARCHAR(200), IN `p_genre` VARCHAR(50), IN `p_composition_data` LONGTEXT)  BEGIN
    INSERT INTO music_compositions (
        user_id, composition_id, title, genre, composition_data
    ) VALUES (
        p_user_id, p_composition_id, p_title, p_genre, p_composition_data
    );
    
    SELECT LAST_INSERT_ID() as id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSystemStats` ()  BEGIN
    SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
        (SELECT COUNT(*) FROM users WHERE premium_status = 'premium') as premium_users,
        (SELECT COUNT(*) FROM security_events WHERE DATE(created_at) = CURDATE()) as threats_detected_today,
        (SELECT COUNT(*) FROM ai_detections WHERE DATE(created_at) = CURDATE()) as ai_detections_today,
        (SELECT COUNT(*) FROM conversations WHERE DATE(created_at) = CURDATE()) as conversations_today,
        (SELECT COUNT(*) FROM conversation_messages WHERE DATE(created_at) = CURDATE()) as messages_today;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserCompositions` (IN `p_user_id` INT, IN `p_limit` INT)  BEGIN
    SELECT * FROM v_user_compositions 
    WHERE user_id = p_user_id 
    ORDER BY created_at DESC 
    LIMIT p_limit;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `LogStudioAction` (IN `p_user_id` INT, IN `p_session_id` VARCHAR(100), IN `p_action_type` VARCHAR(50), IN `p_action_details` JSON, IN `p_composition_id` VARCHAR(50), IN `p_duration_seconds` INT)  BEGIN
    INSERT INTO studio_analytics (
        user_id, session_id, action_type, action_details, 
        composition_id, duration_seconds
    ) VALUES (
        p_user_id, p_session_id, p_action_type, p_action_details,
        p_composition_id, p_duration_seconds
    );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `access_logs`
--

CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_uri` text COLLATE utf8mb4_unicode_ci,
  `request_method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `referer` text COLLATE utf8mb4_unicode_ci,
  `status_code` int(11) DEFAULT NULL,
  `response_time` float DEFAULT NULL,
  `accessed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ai_detections`
--

CREATE TABLE `ai_detections` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `conversation_id` int(11) DEFAULT NULL,
  `message_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence_score` decimal(3,2) NOT NULL,
  `detection_patterns` json DEFAULT NULL,
  `neural_analysis` json DEFAULT NULL,
  `threat_level` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'low',
  `is_false_positive` tinyint(1) DEFAULT '0',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `assistant_conversations`
--

CREATE TABLE `assistant_conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('user','assistant') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `context_data` json DEFAULT NULL,
  `emotion_detected` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intent_detected` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_confidence` decimal(3,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audio_effects`
--

CREATE TABLE `audio_effects` (
  `id` int(11) NOT NULL,
  `effect_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parameters` json DEFAULT NULL,
  `compatible_genres` json DEFAULT NULL,
  `processing_cost` decimal(3,2) DEFAULT '1.00',
  `is_premium` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `audio_effects`
--

INSERT INTO `audio_effects` (`id`, `effect_name`, `display_name`, `category`, `description`, `parameters`, `compatible_genres`, `processing_cost`, `is_premium`, `is_active`, `created_at`) VALUES
(1, 'reverb', 'Reverb', 'spatial', 'Añade espacialidad y profundidad al sonido', '{\"damping\": 0.3, \"room_size\": 0.5, \"wet_level\": 0.2}', '[\"rap\", \"reggaeton\", \"trap\", \"pop\", \"rock\", \"electronic\"]', '1.00', 0, 1, '2025-09-08 15:07:13'),
(2, 'delay', 'Delay/Echo', 'temporal', 'Crea repeticiones del sonido con retraso', '{\"feedback\": 0.3, \"wet_level\": 0.15, \"delay_time\": 0.25}', '[\"rap\", \"reggaeton\", \"trap\", \"pop\", \"rock\", \"electronic\"]', '1.00', 0, 1, '2025-09-08 15:07:13'),
(3, 'auto_tune', 'Auto-Tune', 'pitch', 'Corrección automática de afinación', '{\"scale\": \"chromatic\", \"sensitivity\": 0.7, \"correction_speed\": 0.8}', '[\"rap\", \"reggaeton\", \"trap\", \"pop\"]', '1.00', 0, 1, '2025-09-08 15:07:13'),
(4, 'compressor', 'Compresor', 'dynamics', 'Controla la dinámica del audio', '{\"ratio\": 4, \"attack\": 0.003, \"release\": 0.1, \"threshold\": -12}', '[\"rap\", \"reggaeton\", \"trap\", \"pop\", \"rock\", \"electronic\"]', '1.00', 0, 1, '2025-09-08 15:07:13'),
(5, 'distortion', 'Distorsión', 'saturation', 'Añade saturación y carácter al sonido', '{\"tone\": 0.5, \"drive\": 0.3, \"level\": 0.8}', '[\"rap\", \"trap\", \"rock\"]', '1.00', 0, 1, '2025-09-08 15:07:13'),
(6, 'chorus', 'Chorus', 'modulation', 'Crea un efecto de coro y amplitud', '{\"mix\": 0.25, \"rate\": 0.5, \"depth\": 0.3}', '[\"pop\", \"rock\", \"electronic\"]', '1.00', 0, 1, '2025-09-08 15:07:13'),
(7, 'eq', 'Ecualizador', 'frequency', 'Ajusta el balance de frecuencias', '{\"low\": 0, \"mid\": 0, \"high\": 0, \"low_freq\": 100, \"high_freq\": 10000}', '[\"rap\", \"reggaeton\", \"trap\", \"pop\", \"rock\", \"electronic\"]', '1.00', 0, 1, '2025-09-08 15:07:13'),
(8, 'noise_gate', 'Noise Gate', 'dynamics', 'Elimina ruido de fondo', '{\"ratio\": 10, \"attack\": 0.001, \"release\": 0.1, \"threshold\": -40}', '[\"rap\", \"reggaeton\", \"trap\", \"pop\", \"rock\", \"electronic\"]', '1.00', 0, 1, '2025-09-08 15:07:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `audio_recordings`
--

CREATE TABLE `audio_recordings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `composition_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_type` enum('voice_input','vocal_recording','instrument') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'voice_input',
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int(11) DEFAULT '0',
  `duration` decimal(6,2) DEFAULT '0.00',
  `quality_score` decimal(3,2) DEFAULT '0.00',
  `transcription` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `emotion_analysis` json DEFAULT NULL,
  `audio_analysis` json DEFAULT NULL,
  `processing_status` enum('pending','processing','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `processed_file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effects_applied` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `audio_recordings`
--
DELIMITER $$
CREATE TRIGGER `tr_recording_quality_check` BEFORE INSERT ON `audio_recordings` FOR EACH ROW BEGIN
    IF NEW.quality_score < 0.5 THEN
        SET NEW.processing_status = 'failed';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chatbot_messages`
--

CREATE TABLE `chatbot_messages` (
  `id` int(11) NOT NULL,
  `message_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversation_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence_score` decimal(3,2) DEFAULT '0.80',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Nueva Conversación',
  `conversation_type` enum('chat','ai_detection','security') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'chat',
  `status` enum('active','archived','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `message_count` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conversation_logs`
--

CREATE TABLE `conversation_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `conversation_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conversation_messages`
--

CREATE TABLE `conversation_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message_type` enum('user','ai','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ai_confidence_score` decimal(3,2) DEFAULT NULL,
  `threat_detected` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `conversation_messages`
--
DELIMITER $$
CREATE TRIGGER `update_conversation_count` AFTER INSERT ON `conversation_messages` FOR EACH ROW BEGIN
    UPDATE conversations 
    SET message_count = message_count + 1, 
        updated_at = NOW()
    WHERE id = NEW.conversation_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `device_backups`
--

CREATE TABLE `device_backups` (
  `id` int(11) NOT NULL,
  `device_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `backup_type` enum('full','partial','contacts','photos','documents') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'full',
  `size_mb` decimal(10,2) DEFAULT NULL,
  `file_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `encryption_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `device_locations`
--

CREATE TABLE `device_locations` (
  `id` int(11) NOT NULL,
  `device_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `accuracy` int(11) DEFAULT '10',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `device_locations`
--

INSERT INTO `device_locations` (`id`, `device_id`, `latitude`, `longitude`, `accuracy`, `address`, `city`, `country`, `ip_address`, `timestamp`) VALUES
(1, 'DEV-001', '4.71100000', '-74.07210000', 10, NULL, 'Bogotá', 'Colombia', NULL, '2025-09-09 03:28:28'),
(2, 'DEV-002', '3.45160000', '-76.53200000', 15, NULL, 'Cali', 'Colombia', NULL, '2025-09-09 03:28:28'),
(3, 'DEV-003', '4.71100000', '-74.07210000', 8, NULL, 'Bogotá', 'Colombia', NULL, '2025-09-09 03:28:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `firewall_rules`
--

CREATE TABLE `firewall_rules` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_type` enum('ip_block','pattern','rate_limit','geo_block','user_agent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pattern',
  `rule_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` enum('block','allow','monitor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'block',
  `priority` int(11) DEFAULT '100',
  `enabled` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `genre_templates`
--

CREATE TABLE `genre_templates` (
  `id` int(11) NOT NULL,
  `genre_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bpm_min` int(11) DEFAULT '60',
  `bpm_max` int(11) DEFAULT '180',
  `common_keys` json DEFAULT NULL,
  `typical_instruments` json DEFAULT NULL,
  `common_effects` json DEFAULT NULL,
  `song_structure` json DEFAULT NULL,
  `lyrical_themes` json DEFAULT NULL,
  `vocal_styles` json DEFAULT NULL,
  `production_tips` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `genre_templates`
--

INSERT INTO `genre_templates` (`id`, `genre_name`, `display_name`, `bpm_min`, `bpm_max`, `common_keys`, `typical_instruments`, `common_effects`, `song_structure`, `lyrical_themes`, `vocal_styles`, `production_tips`, `is_active`, `created_at`) VALUES
(1, 'rap', 'RAP/Hip-Hop', 70, 140, '[\"Am\", \"Dm\", \"Em\", \"Gm\", \"Cm\"]', '[\"808_drums\", \"synth_bass\", \"piano\", \"strings\", \"vocal_chops\"]', NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 05:19:03'),
(2, 'reggaeton', 'Reggaeton', 90, 100, '[\"Am\", \"Dm\", \"Gm\"]', '[\"dembow_drums\", \"synth_bass\", \"piano\", \"horn_section\"]', NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 05:19:03'),
(3, 'trap', 'Trap', 130, 170, '[\"Am\", \"Dm\", \"Em\", \"Fm\"]', '[\"trap_drums\", \"808_bass\", \"synth_lead\", \"vocal_chops\"]', NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 05:19:03'),
(4, 'pop', 'Pop', 100, 130, '[\"C\", \"G\", \"Am\", \"F\", \"Dm\"]', '[\"drums\", \"bass\", \"piano\", \"guitar\", \"strings\"]', NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 05:19:03'),
(5, 'rock', 'Rock', 110, 150, '[\"E\", \"A\", \"D\", \"G\", \"Em\"]', '[\"drums\", \"electric_guitar\", \"bass_guitar\", \"vocals\"]', NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 05:22:21'),
(6, 'electronic', 'Electronic/EDM', 120, 140, '[\"Am\", \"Em\", \"Dm\", \"Gm\"]', '[\"electronic_drums\", \"synth_bass\", \"synth_lead\", \"pad\"]', NULL, NULL, NULL, NULL, NULL, 1, '2025-09-10 05:22:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `military_logs`
--

CREATE TABLE `military_logs` (
  `id` int(11) NOT NULL,
  `classification` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `integrity_hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantum_timestamp` decimal(20,6) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `musical_ideas`
--

CREATE TABLE `musical_ideas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `audio_recording_id` int(11) DEFAULT NULL,
  `original_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `extracted_theme` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detected_genre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `detected_mood` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `suggested_tempo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `key_phrases` json DEFAULT NULL,
  `emotion_keywords` json DEFAULT NULL,
  `musical_elements` json DEFAULT NULL,
  `ai_suggestions` json DEFAULT NULL,
  `confidence_score` decimal(3,2) DEFAULT '0.00',
  `used_in_composition` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `music_compositions`
--

CREATE TABLE `music_compositions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `composition_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `genre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `bpm` int(11) DEFAULT '120',
  `key_signature` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'C',
  `theme` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `mood` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'neutral',
  `structure` json DEFAULT NULL,
  `instruments` json DEFAULT NULL,
  `effects` json DEFAULT NULL,
  `lyrics_suggestions` json DEFAULT NULL,
  `vocal_style` json DEFAULT NULL,
  `composition_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `preserve_original_voice` tinyint(1) DEFAULT '1',
  `ai_confidence` decimal(3,2) DEFAULT '0.85',
  `status` enum('draft','completed','published') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `music_compositions`
--
DELIMITER $$
CREATE TRIGGER `tr_composition_updated` AFTER UPDATE ON `music_compositions` FOR EACH ROW BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        INSERT INTO studio_analytics (
            user_id, session_id, action_type, action_details, composition_id
        ) VALUES (
            NEW.user_id, 
            CONCAT('AUTO_', NEW.id), 
            'composition_completed',
            JSON_OBJECT('title', NEW.title, 'genre', NEW.genre),
            NEW.composition_id
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('info','warning','error','success','security') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT '0',
  `action_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `performance_metrics`
--

CREATE TABLE `performance_metrics` (
  `id` int(11) NOT NULL,
  `metric_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `metric_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_value` decimal(10,2) NOT NULL,
  `metric_unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `collected_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `premium_features`
--

CREATE TABLE `premium_features` (
  `id` int(11) NOT NULL,
  `feature_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `feature_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `premium_only` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `premium_memberships`
--

CREATE TABLE `premium_memberships` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_type` enum('monthly','annual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'COP',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','expired','cancelled','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `auto_renew` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `protected_devices`
--

CREATE TABLE `protected_devices` (
  `id` int(11) NOT NULL,
  `device_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('laptop','mobile','tablet','desktop','wearable','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'other',
  `status` enum('secure','warning','alert','lost','stolen') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'secure',
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `last_seen` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `battery` int(11) DEFAULT '100',
  `is_locked` tinyint(1) DEFAULT '0',
  `tracking_enabled` tinyint(1) DEFAULT '1',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imei` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(17) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `protected_devices`
--

INSERT INTO `protected_devices` (`id`, `device_id`, `user_id`, `name`, `type`, `status`, `location`, `latitude`, `longitude`, `battery`, `is_locked`, `tracking_enabled`, `ip_address`, `os`, `serial_number`, `imei`, `mac_address`, `created_at`) VALUES
(1, 'DEV-001', 1, 'Laptop Principal', 'laptop', 'secure', 'Bogotá, Colombia', '4.71100000', '-74.07210000', 87, 0, 1, '181.49.23.145', 'Windows 11 Pro', NULL, NULL, NULL, '2025-09-09 08:28:28'),
(2, 'DEV-002', 1, 'Smartphone Personal', 'mobile', 'warning', 'Cali, Colombia', '3.45160000', '-76.53200000', 45, 0, 1, '190.85.46.22', 'Android 14', NULL, NULL, NULL, '2025-09-09 08:28:28'),
(3, 'DEV-003', 1, 'Tablet Trabajo', 'tablet', 'secure', 'Bogotá, Colombia', '4.71100000', '-74.07210000', 92, 0, 1, '181.49.23.145', 'iPadOS 17', NULL, NULL, NULL, '2025-09-09 08:28:28'),
(4, 'DEV-004', 1, 'PC Gaming', 'desktop', 'secure', 'Bogotá, Colombia', '4.71100000', '-74.07210000', 100, 0, 1, '181.49.23.145', 'Windows 11 Gaming', NULL, NULL, NULL, '2025-09-09 08:28:28'),
(5, 'DEV-005', 1, 'SmartWatch', 'wearable', 'alert', 'Desconocida', NULL, NULL, 12, 0, 1, NULL, 'WearOS 4', NULL, NULL, NULL, '2025-09-09 08:28:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quantum_keys`
--

CREATE TABLE `quantum_keys` (
  `id` int(11) NOT NULL,
  `key_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `key_type` enum('BB84','E91','B92','SARG04','MDI-QKD') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'BB84',
  `key_length` int(11) DEFAULT '256',
  `key_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `security_parameter` decimal(5,4) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quantum_sessions`
--

CREATE TABLE `quantum_sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `quantum_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bb84_result` json DEFAULT NULL,
  `entanglement_pairs` int(11) DEFAULT '0',
  `fidelity` decimal(5,4) DEFAULT '0.0000',
  `error_rate` decimal(5,4) DEFAULT '0.0000',
  `status` enum('active','completed','failed','intercepted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `requests_count` int(11) DEFAULT '1',
  `window_start` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `security_actions`
--

CREATE TABLE `security_actions` (
  `id` int(11) NOT NULL,
  `device_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type` enum('lock','unlock','wipe','alarm','backup','track','panic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `initiated_by` int(11) NOT NULL,
  `status` enum('pending','in_progress','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `security_alerts`
--

CREATE TABLE `security_alerts` (
  `id` int(11) NOT NULL,
  `device_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alert_type` enum('device_lost','unusual_location','login_attempt','low_battery','device_offline','unauthorized_access','panic_activated') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `is_resolved` tinyint(1) DEFAULT '0',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `security_alerts`
--

INSERT INTO `security_alerts` (`id`, `device_id`, `alert_type`, `message`, `severity`, `is_resolved`, `resolved_at`, `resolved_by`, `created_at`) VALUES
(1, 'DEV-005', 'device_lost', 'SmartWatch no detectado por 24 horas', 'high', 0, NULL, NULL, '2025-09-09 08:28:28'),
(2, 'DEV-002', 'unusual_location', 'Smartphone detectado en ubicación inusual', 'medium', 0, NULL, NULL, '2025-09-09 08:28:28'),
(3, 'DEV-001', 'login_attempt', '3 intentos de acceso fallidos', 'low', 0, NULL, NULL, '2025-09-09 08:28:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `security_events`
--

CREATE TABLE `security_events` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `event_data` json DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT '0',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `security_events`
--

INSERT INTO `security_events` (`id`, `user_id`, `event_type`, `description`, `severity`, `ip_address`, `user_agent`, `event_data`, `resolved`, `resolved_by`, `resolved_at`, `created_at`) VALUES
(1, 1, 'functions_loaded', 'Guardian compatibility functions loaded successfully', 'low', '::1', NULL, NULL, 0, NULL, NULL, '2025-09-10 05:46:59'),
(2, 1, 'functions_loaded', 'Guardian compatibility functions loaded successfully', 'low', '::1', NULL, NULL, 0, NULL, NULL, '2025-09-10 05:47:00'),
(3, 1, 'functions_loaded', 'Guardian compatibility functions loaded successfully', 'low', '::1', NULL, NULL, 0, NULL, NULL, '2025-09-10 05:47:01');

--
-- Disparadores `security_events`
--
DELIMITER $$
CREATE TRIGGER `log_security_event` AFTER INSERT ON `security_events` FOR EACH ROW BEGIN
    INSERT INTO system_logs (level, message, context, user_id, created_at)
    VALUES (
        'WARNING',
        CONCAT('Evento de seguridad: ', NEW.event_type),
        JSON_OBJECT('event_id', NEW.id, 'severity', NEW.severity, 'ip', NEW.ip_address),
        NEW.user_id,
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `security_summary`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `security_summary` (
`date` date
,`event_count` bigint(21)
,`resolved_count` bigint(21)
,`severity` enum('low','medium','high','critical')
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `studio_analytics`
--

CREATE TABLE `studio_analytics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_details` json DEFAULT NULL,
  `composition_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT '0',
  `success` tinyint(1) DEFAULT '1',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `studio_projects`
--

CREATE TABLE `studio_projects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `genre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_audience` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_status` enum('planning','recording','mixing','mastering','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'planning',
  `compositions` json DEFAULT NULL,
  `recordings` json DEFAULT NULL,
  `project_settings` json DEFAULT NULL,
  `collaboration_settings` json DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `studio_user_settings`
--

CREATE TABLE `studio_user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preferred_genres` json DEFAULT NULL,
  `default_bpm` int(11) DEFAULT '120',
  `default_key` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'C',
  `voice_processing_preferences` json DEFAULT NULL,
  `assistant_personality` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'creativa_amigable',
  `audio_quality_settings` json DEFAULT NULL,
  `notification_preferences` json DEFAULT NULL,
  `studio_theme` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'dark',
  `language` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'es',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `studio_user_settings`
--

INSERT INTO `studio_user_settings` (`id`, `user_id`, `preferred_genres`, `default_bpm`, `default_key`, `voice_processing_preferences`, `assistant_personality`, `audio_quality_settings`, `notification_preferences`, `studio_theme`, `language`, `created_at`) VALUES
(1, 1, '[\"rap\", \"trap\", \"reggaeton\"]', 120, 'Am', NULL, 'creativa_amigable', NULL, NULL, 'dark', 'es', '2025-09-10 05:22:21'),
(2, 2, '[\"pop\", \"rock\"]', 110, 'C', NULL, 'creativa_amigable', NULL, NULL, 'dark', 'es', '2025-09-10 05:22:21');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_config`
--

CREATE TABLE `system_config` (
  `id` int(11) NOT NULL,
  `config_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `config_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `config_type` enum('string','integer','boolean','json') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `level` enum('DEBUG','INFO','WARNING','ERROR','CRITICAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` json DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `system_logs`
--

INSERT INTO `system_logs` (`id`, `level`, `message`, `context`, `user_id`, `ip_address`, `created_at`) VALUES
(1, 'WARNING', 'Evento de seguridad: functions_loaded', '{\"ip\": \"::1\", \"event_id\": 1, \"severity\": \"low\"}', 1, NULL, '2025-09-10 05:46:59'),
(2, 'WARNING', 'Evento de seguridad: functions_loaded', '{\"ip\": \"::1\", \"event_id\": 2, \"severity\": \"low\"}', 1, NULL, '2025-09-10 05:47:00'),
(3, 'WARNING', 'Evento de seguridad: functions_loaded', '{\"ip\": \"::1\", \"event_id\": 3, \"severity\": \"low\"}', 1, NULL, '2025-09-10 05:47:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `threat_events`
--

CREATE TABLE `threat_events` (
  `id` int(11) NOT NULL,
  `event_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `threat_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity_level` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `source_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usage_stats`
--

CREATE TABLE `usage_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `messages_sent` int(11) DEFAULT '0',
  `conversations_started` int(11) DEFAULT '0',
  `ai_detections` int(11) DEFAULT '0',
  `features_used` json DEFAULT NULL,
  `session_duration` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('admin','premium','basic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'basic',
  `premium_status` enum('premium','basic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'basic',
  `premium_expires_at` datetime DEFAULT NULL,
  `status` enum('active','inactive','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `login_attempts` int(11) DEFAULT '0',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `security_clearance` enum('UNCLASSIFIED','CONFIDENTIAL','SECRET','TOP_SECRET') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'UNCLASSIFIED',
  `military_access` tinyint(1) DEFAULT '0',
  `failed_login_attempts` int(11) DEFAULT '0',
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `password_hash`, `email`, `fullname`, `user_type`, `premium_status`, `premium_expires_at`, `status`, `login_attempts`, `last_login`, `created_at`, `security_clearance`, `military_access`, `failed_login_attempts`, `locked_until`) VALUES
(1, 'anderson', 'Ander12345@', '$2y$10$BxVJKFQE5YCPBwHGYpPuN.QqT5YU5qXFMKhFQxzGVk2OJGqXKhAe2', 'anderson@guardianai.com', 'Anderson Mamian Chicangana', 'admin', 'premium', NULL, 'active', 0, NULL, '2025-08-23 10:00:00', 'TOP_SECRET', 1, 0, NULL),
(2, 'admin', 'admin123', '$2y$10$4gD.2rTKxR5cZ0MZwmTY/e3KGqGJf9HpQRQzBk5NrXQ9EqmNzZGGe', 'admin@guardianai.com', 'Administrador GuardianIA', 'admin', 'basic', NULL, 'active', 0, NULL, '2025-08-23 10:00:00', 'SECRET', 0, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `user_stats`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `user_stats` (
`ai_detections` bigint(21)
,`id` int(11)
,`last_activity` datetime
,`premium_status` enum('premium','basic')
,`total_conversations` bigint(21)
,`total_messages` bigint(21)
,`username` varchar(50)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vault_files`
--

CREATE TABLE `vault_files` (
  `id` int(11) NOT NULL,
  `file_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `encrypted_path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upload_date` datetime DEFAULT NULL,
  `last_access` datetime DEFAULT NULL,
  `encryption_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `integrity_hash` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_user_compositions`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_user_compositions` (
`ai_confidence` decimal(3,2)
,`avg_quality` decimal(7,6)
,`bpm` int(11)
,`composition_id` varchar(50)
,`created_at` timestamp
,`genre` varchar(50)
,`id` int(11)
,`key_signature` varchar(10)
,`mood` varchar(50)
,`recording_count` bigint(21)
,`status` enum('draft','completed','published')
,`theme` varchar(200)
,`title` varchar(200)
,`user_id` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_user_studio_stats`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_user_studio_stats` (
`avg_quality` decimal(7,6)
,`last_activity` timestamp
,`total_compositions` bigint(21)
,`total_duration` decimal(28,2)
,`total_recordings` bigint(21)
,`user_id` int(11)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `web_threats`
--

CREATE TABLE `web_threats` (
  `id` int(11) NOT NULL,
  `threat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `threat_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `severity` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `status` enum('blocked','allowed','monitoring') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'blocked',
  `detection_method` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `detected_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura para la vista `security_summary`
--
DROP TABLE IF EXISTS `security_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `security_summary`  AS  select cast(`security_events`.`created_at` as date) AS `date`,`security_events`.`severity` AS `severity`,count(0) AS `event_count`,count((case when (`security_events`.`resolved` = true) then 1 end)) AS `resolved_count` from `security_events` where (`security_events`.`created_at` >= (now() - interval 30 day)) group by cast(`security_events`.`created_at` as date),`security_events`.`severity` order by `date` desc,`security_events`.`severity` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `user_stats`
--
DROP TABLE IF EXISTS `user_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_stats`  AS  select `u`.`id` AS `id`,`u`.`username` AS `username`,`u`.`premium_status` AS `premium_status`,count(distinct `c`.`id`) AS `total_conversations`,count(distinct `cm`.`id`) AS `total_messages`,count(distinct `ad`.`id`) AS `ai_detections`,max(`u`.`last_login`) AS `last_activity` from (((`users` `u` left join `conversations` `c` on((`u`.`id` = `c`.`user_id`))) left join `conversation_messages` `cm` on((`u`.`id` = `cm`.`user_id`))) left join `ai_detections` `ad` on((`u`.`id` = `ad`.`user_id`))) where (`u`.`status` = 'active') group by `u`.`id`,`u`.`username`,`u`.`premium_status` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_user_compositions`
--
DROP TABLE IF EXISTS `v_user_compositions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_compositions`  AS  select `mc`.`id` AS `id`,`mc`.`user_id` AS `user_id`,`mc`.`composition_id` AS `composition_id`,`mc`.`title` AS `title`,`mc`.`genre` AS `genre`,`mc`.`bpm` AS `bpm`,`mc`.`key_signature` AS `key_signature`,`mc`.`theme` AS `theme`,`mc`.`mood` AS `mood`,`mc`.`status` AS `status`,`mc`.`ai_confidence` AS `ai_confidence`,`mc`.`created_at` AS `created_at`,count(`ar`.`id`) AS `recording_count`,avg(`ar`.`quality_score`) AS `avg_quality` from (`music_compositions` `mc` left join `audio_recordings` `ar` on((`mc`.`composition_id` = `ar`.`composition_id`))) group by `mc`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_user_studio_stats`
--
DROP TABLE IF EXISTS `v_user_studio_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_studio_stats`  AS  select `audio_recordings`.`user_id` AS `user_id`,count(distinct `audio_recordings`.`composition_id`) AS `total_compositions`,count(0) AS `total_recordings`,avg(`audio_recordings`.`quality_score`) AS `avg_quality`,sum(`audio_recordings`.`duration`) AS `total_duration`,max(`audio_recordings`.`created_at`) AS `last_activity` from `audio_recordings` group by `audio_recordings`.`user_id` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_date` (`accessed_at`);

--
-- Indices de la tabla `ai_detections`
--
ALTER TABLE `ai_detections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_confidence` (`confidence_score`),
  ADD KEY `idx_threat_level` (`threat_level`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_ai_detection_analysis` (`user_id`,`threat_level`,`created_at`);

--
-- Indices de la tabla `assistant_conversations`
--
ALTER TABLE `assistant_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_conversations_user_session` (`user_id`,`session_id`);

--
-- Indices de la tabla `audio_effects`
--
ALTER TABLE `audio_effects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_effect_name` (`effect_name`);

--
-- Indices de la tabla `audio_recordings`
--
ALTER TABLE `audio_recordings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_composition_id` (`composition_id`),
  ADD KEY `idx_recording_type` (`recording_type`),
  ADD KEY `idx_recordings_user_type` (`user_id`,`recording_type`);

--
-- Indices de la tabla `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_id` (`message_id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user_conversations` (`user_id`,`status`,`created_at`);

--
-- Indices de la tabla `conversation_logs`
--
ALTER TABLE `conversation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_action` (`action_type`);

--
-- Indices de la tabla `conversation_messages`
--
ALTER TABLE `conversation_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`message_type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_message_search` (`user_id`,`created_at`,`message_type`);

--
-- Indices de la tabla `device_backups`
--
ALTER TABLE `device_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_backup` (`device_id`,`created_at`);

--
-- Indices de la tabla `device_locations`
--
ALTER TABLE `device_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_timestamp` (`device_id`,`timestamp`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indices de la tabla `firewall_rules`
--
ALTER TABLE `firewall_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_enabled` (`enabled`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indices de la tabla `genre_templates`
--
ALTER TABLE `genre_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_genre_name` (`genre_name`);

--
-- Indices de la tabla `military_logs`
--
ALTER TABLE `military_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_classification` (`classification`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `musical_ideas`
--
ALTER TABLE `musical_ideas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_audio_recording_id` (`audio_recording_id`),
  ADD KEY `idx_detected_genre` (`detected_genre`),
  ADD KEY `idx_ideas_user_genre` (`user_id`,`detected_genre`);

--
-- Indices de la tabla `music_compositions`
--
ALTER TABLE `music_compositions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `composition_id` (`composition_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_genre` (`genre`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_compositions_user_genre` (`user_id`,`genre`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_unread` (`user_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `metric_id` (`metric_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `premium_features`
--
ALTER TABLE `premium_features`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `feature_key` (`feature_key`),
  ADD KEY `idx_key` (`feature_key`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indices de la tabla `premium_memberships`
--
ALTER TABLE `premium_memberships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indices de la tabla `protected_devices`
--
ALTER TABLE `protected_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_id` (`device_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_last_seen` (`last_seen`);

--
-- Indices de la tabla `quantum_keys`
--
ALTER TABLE `quantum_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_id` (`key_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_key_id` (`key_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indices de la tabla `quantum_sessions`
--
ALTER TABLE `quantum_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rate_limit` (`ip_address`,`endpoint`,`window_start`),
  ADD KEY `idx_ip` (`ip_address`),
  ADD KEY `idx_window` (`window_start`);

--
-- Indices de la tabla `security_actions`
--
ALTER TABLE `security_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_action` (`device_id`,`action_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `security_alerts`
--
ALTER TABLE `security_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_device_id` (`device_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indices de la tabla `security_events`
--
ALTER TABLE `security_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_resolved` (`resolved`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `security_events_ibfk_2` (`resolved_by`);

--
-- Indices de la tabla `studio_analytics`
--
ALTER TABLE `studio_analytics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_analytics_user_action` (`user_id`,`action_type`);

--
-- Indices de la tabla `studio_projects`
--
ALTER TABLE `studio_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_project_status` (`project_status`);

--
-- Indices de la tabla `studio_user_settings`
--
ALTER TABLE `studio_user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`),
  ADD KEY `idx_key` (`config_key`);

--
-- Indices de la tabla `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indices de la tabla `threat_events`
--
ALTER TABLE `threat_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_threat_type` (`threat_type`);

--
-- Indices de la tabla `usage_stats`
--
ALTER TABLE `usage_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`date`),
  ADD KEY `idx_user_date` (`user_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indices de la tabla `vault_files`
--
ALTER TABLE `vault_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_id` (`file_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_file_id` (`file_id`);

--
-- Indices de la tabla `web_threats`
--
ALTER TABLE `web_threats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `threat_id` (`threat_id`),
  ADD KEY `idx_ip` (`source_ip`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_date` (`detected_at`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ai_detections`
--
ALTER TABLE `ai_detections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `assistant_conversations`
--
ALTER TABLE `assistant_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `audio_effects`
--
ALTER TABLE `audio_effects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `audio_recordings`
--
ALTER TABLE `audio_recordings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `conversation_logs`
--
ALTER TABLE `conversation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `conversation_messages`
--
ALTER TABLE `conversation_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de la tabla `device_backups`
--
ALTER TABLE `device_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `device_locations`
--
ALTER TABLE `device_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `firewall_rules`
--
ALTER TABLE `firewall_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `genre_templates`
--
ALTER TABLE `genre_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `military_logs`
--
ALTER TABLE `military_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `musical_ideas`
--
ALTER TABLE `musical_ideas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `music_compositions`
--
ALTER TABLE `music_compositions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `performance_metrics`
--
ALTER TABLE `performance_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `premium_features`
--
ALTER TABLE `premium_features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `premium_memberships`
--
ALTER TABLE `premium_memberships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `protected_devices`
--
ALTER TABLE `protected_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `quantum_keys`
--
ALTER TABLE `quantum_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `quantum_sessions`
--
ALTER TABLE `quantum_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `security_actions`
--
ALTER TABLE `security_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `security_alerts`
--
ALTER TABLE `security_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `security_events`
--
ALTER TABLE `security_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `studio_analytics`
--
ALTER TABLE `studio_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `studio_projects`
--
ALTER TABLE `studio_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `studio_user_settings`
--
ALTER TABLE `studio_user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `system_config`
--
ALTER TABLE `system_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `threat_events`
--
ALTER TABLE `threat_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usage_stats`
--
ALTER TABLE `usage_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vault_files`
--
ALTER TABLE `vault_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `web_threats`
--
ALTER TABLE `web_threats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ai_detections`
--
ALTER TABLE `ai_detections`
  ADD CONSTRAINT `ai_detections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ai_detections_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ai_detections_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `conversation_logs`
--
ALTER TABLE `conversation_logs`
  ADD CONSTRAINT `conversation_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `conversation_logs_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `conversation_messages`
--
ALTER TABLE `conversation_messages`
  ADD CONSTRAINT `conversation_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `military_logs`
--
ALTER TABLE `military_logs`
  ADD CONSTRAINT `military_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `premium_memberships`
--
ALTER TABLE `premium_memberships`
  ADD CONSTRAINT `premium_memberships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `quantum_keys`
--
ALTER TABLE `quantum_keys`
  ADD CONSTRAINT `quantum_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `quantum_sessions`
--
ALTER TABLE `quantum_sessions`
  ADD CONSTRAINT `quantum_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `security_events`
--
ALTER TABLE `security_events`
  ADD CONSTRAINT `security_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `security_events_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usage_stats`
--
ALTER TABLE `usage_stats`
  ADD CONSTRAINT `usage_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
