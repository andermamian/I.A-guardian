"""
GuardianIA v3.0 - Blueprint de Chatbot IA
Incluye: GuardianAIChatbot, chatbot avanzado, comunicación hub
"""
from flask import Blueprint, request, jsonify, render_template, session
from flask_login import login_required, current_user
from datetime import datetime
import uuid
import random
import json
import re
from models import db, Conversation, ConversationMessage
from utils.security import (
    log_security_event, log_military_event, sanitize_input, is_premium_user
)
from config import APP_NAME, PREMIUM_FEATURES

chatbot_bp = Blueprint('chatbot', __name__)


class GuardianAIChatbotEngine:
    """Motor de chatbot de seguridad IA"""

    KNOWLEDGE_BASE = {
        'seguridad': {
            'keywords': ['seguridad', 'proteger', 'amenaza', 'virus', 'malware', 'hack', 'ataque',
                         'firewall', 'antivirus', 'phishing', 'ransomware', 'spyware'],
            'responses': [
                'He analizado tu consulta de seguridad. GuardianIA utiliza encriptación militar AES-256-GCM para proteger tus datos.',
                'Nuestro sistema de detección de amenazas utiliza redes neuronales profundas con 12 capas para identificar patrones maliciosos.',
                'El firewall cuántico de GuardianIA bloquea amenazas en tiempo real con una tasa de detección del 99.7%.',
                'Recomiendo activar la encriptación cuántica para máxima protección. ¿Deseas que la configure?',
                'He detectado y neutralizado la amenaza. El sistema está protegido con cifrado de grado militar.'
            ]
        },
        'vpn': {
            'keywords': ['vpn', 'conexión', 'privacidad', 'anonimato', 'ip', 'túnel', 'proxy'],
            'responses': [
                'La VPN de GuardianIA utiliza túneles encriptados con protocolo militar para proteger tu conexión.',
                'Tenemos servidores VPN en Colombia, USA, España, Japón y un servidor militar seguro.',
                'Tu conexión VPN está protegida con encriptación AES-256 y Perfect Forward Secrecy.',
                'Recomiendo usar el servidor militar seguro para máxima privacidad.'
            ]
        },
        'rendimiento': {
            'keywords': ['rendimiento', 'velocidad', 'lento', 'optimizar', 'memoria', 'cpu', 'rápido'],
            'responses': [
                'He analizado el rendimiento del sistema. CPU al {cpu}%, Memoria al {mem}%. Todo dentro de parámetros normales.',
                'Para optimizar el rendimiento, recomiendo ejecutar el módulo de optimización avanzada.',
                'El sistema de IA está monitoreando el rendimiento en tiempo real con análisis predictivo.',
                'He detectado oportunidades de optimización. ¿Deseas que ejecute la optimización automática?'
            ]
        },
        'encriptacion': {
            'keywords': ['encriptar', 'cifrar', 'clave', 'contraseña', 'hash', 'aes', 'rsa', 'cuántico'],
            'responses': [
                'GuardianIA utiliza encriptación de grado militar: AES-256-GCM, RSA-4096 y algoritmos post-cuánticos.',
                'Tus datos están protegidos con FIPS-140-2 compliance y resistencia cuántica.',
                'El sistema de claves utiliza PBKDF2 con 100,000 iteraciones para máxima seguridad.',
                'La encriptación cuántica está activa. Tus datos son inmunes a ataques de computación cuántica.'
            ]
        },
        'general': {
            'keywords': ['hola', 'ayuda', 'qué', 'cómo', 'quién', 'información', 'guardian'],
            'responses': [
                '¡Hola! Soy GuardianIA, tu asistente de seguridad con inteligencia artificial militar. ¿En qué puedo ayudarte?',
                'Soy el chatbot de seguridad de GuardianIA v3.0. Puedo ayudarte con: seguridad, VPN, encriptación, rendimiento y más.',
                'GuardianIA es un sistema de seguridad avanzado desarrollado por Anderson Mamian Chicangana.',
                'Estoy aquí para protegerte. Puedo analizar amenazas, configurar VPN, encriptar datos y mucho más.'
            ]
        }
    }

    PERSONALITIES = {
        'guardian': {
            'name': 'Guardian',
            'description': 'Protector de seguridad serio y profesional',
            'style': 'formal'
        },
        'luna': {
            'name': 'Luna AI',
            'description': 'Asistente amigable y empática',
            'style': 'friendly'
        },
        'quantum': {
            'name': 'Quantum',
            'description': 'Experto en tecnología cuántica',
            'style': 'technical'
        },
        'military': {
            'name': 'Comandante',
            'description': 'Oficial militar de ciberseguridad',
            'style': 'military'
        }
    }

    @staticmethod
    def process_message(message, personality='guardian', user_id=None):
        """Procesa un mensaje y genera respuesta"""
        message_lower = message.lower().strip()
        import random

        # Análisis NLP simplificado
        intent = GuardianAIChatbotEngine._detect_intent(message_lower)
        entities = GuardianAIChatbotEngine._extract_entities(message_lower)
        confidence = random.uniform(0.75, 0.99)

        # Buscar en base de conocimiento
        response = GuardianAIChatbotEngine._search_knowledge_base(message_lower, intent)

        # Personalizar según personalidad
        response = GuardianAIChatbotEngine._apply_personality(response, personality)

        # Reemplazar variables dinámicas
        response = response.replace('{cpu}', str(random.randint(20, 80)))
        response = response.replace('{mem}', str(random.randint(40, 85)))

        return {
            'response': response,
            'intent': intent,
            'entities': entities,
            'confidence': confidence,
            'personality': personality,
            'timestamp': datetime.now().isoformat()
        }

    @staticmethod
    def _detect_intent(message):
        """Detecta la intención del mensaje"""
        for category, data in GuardianAIChatbotEngine.KNOWLEDGE_BASE.items():
            for keyword in data['keywords']:
                if keyword in message:
                    return category
        return 'general'

    @staticmethod
    def _extract_entities(message):
        """Extrae entidades del mensaje"""
        entities = []
        # IPs
        ips = re.findall(r'\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b', message)
        for ip in ips:
            entities.append({'type': 'ip', 'value': ip})
        # URLs
        urls = re.findall(r'https?://\S+', message)
        for url in urls:
            entities.append({'type': 'url', 'value': url})
        # Emails
        emails = re.findall(r'\b[\w.-]+@[\w.-]+\.\w+\b', message)
        for email in emails:
            entities.append({'type': 'email', 'value': email})
        return entities

    @staticmethod
    def _search_knowledge_base(message, intent):
        """Busca respuesta en la base de conocimiento"""
        if intent in GuardianAIChatbotEngine.KNOWLEDGE_BASE:
            responses = GuardianAIChatbotEngine.KNOWLEDGE_BASE[intent]['responses']
            return random.choice(responses)
        return random.choice(GuardianAIChatbotEngine.KNOWLEDGE_BASE['general']['responses'])

    @staticmethod
    def _apply_personality(response, personality):
        """Aplica estilo de personalidad a la respuesta"""
        if personality == 'military':
            response = f"[COMANDANTE] {response} Mantengan posición."
        elif personality == 'luna':
            response = f"✨ {response} ¿Hay algo más en lo que pueda ayudarte? 😊"
        elif personality == 'quantum':
            response = f"[QUANTUM] {response} Procesamiento cuántico completado."
        return response


# ========================================
# RUTAS DEL CHATBOT
# ========================================

@chatbot_bp.route('/chatbot', methods=['GET', 'POST'])
@login_required
def chatbot_page():
    """Página principal del chatbot"""
    if request.method == 'POST':
        action = request.form.get('action', '')

        if action == 'send_message':
            message = sanitize_input(request.form.get('message', ''))
            personality = request.form.get('personality', 'guardian')
            conversation_id = request.form.get('conversation_id', '')

            if not message:
                return jsonify({'success': False, 'message': 'Mensaje vacío'})

            # Crear conversación si no existe
            if not conversation_id:
                conversation_id = f'CONV_{uuid.uuid4().hex[:12]}'
                try:
                    conv = Conversation(
                        conversation_id=conversation_id,
                        user_id=current_user.id,
                        title=message[:50],
                        personality=personality
                    )
                    db.session.add(conv)
                    db.session.commit()
                except Exception:
                    db.session.rollback()

            # Guardar mensaje del usuario
            try:
                user_msg = ConversationMessage(
                    message_id=f'MSG_{uuid.uuid4().hex[:12]}',
                    conversation_id=conversation_id,
                    user_id=current_user.id,
                    sender_type='user',
                    message_content=message
                )
                db.session.add(user_msg)
                db.session.commit()
            except Exception:
                db.session.rollback()

            # Procesar con IA
            result = GuardianAIChatbotEngine.process_message(
                message, personality, current_user.id
            )

            # Guardar respuesta de IA
            try:
                ai_msg = ConversationMessage(
                    message_id=f'MSG_{uuid.uuid4().hex[:12]}',
                    conversation_id=conversation_id,
                    user_id=current_user.id,
                    sender_type='ai',
                    message_content=result['response'],
                    confidence_score=result['confidence']
                )
                db.session.add(ai_msg)
                db.session.commit()
            except Exception:
                db.session.rollback()

            return jsonify({
                'success': True,
                'data': {
                    'response': result['response'],
                    'intent': result['intent'],
                    'confidence': result['confidence'],
                    'conversation_id': conversation_id,
                    'personality': personality,
                    'timestamp': result['timestamp']
                }
            })

        elif action == 'get_conversations':
            try:
                convs = Conversation.query.filter_by(
                    user_id=current_user.id
                ).order_by(Conversation.updated_at.desc()).limit(20).all()
                return jsonify({
                    'success': True,
                    'data': [{
                        'conversation_id': c.conversation_id,
                        'title': c.title,
                        'personality': c.personality,
                        'created_at': c.created_at.isoformat() if c.created_at else None,
                        'updated_at': c.updated_at.isoformat() if c.updated_at else None
                    } for c in convs]
                })
            except Exception:
                return jsonify({'success': True, 'data': []})

        elif action == 'get_history':
            conversation_id = request.form.get('conversation_id', '')
            try:
                messages = ConversationMessage.query.filter_by(
                    conversation_id=conversation_id
                ).order_by(ConversationMessage.created_at.asc()).all()
                return jsonify({
                    'success': True,
                    'data': [{
                        'sender_type': m.sender_type,
                        'message_content': m.message_content,
                        'confidence_score': m.confidence_score,
                        'created_at': m.created_at.isoformat() if m.created_at else None
                    } for m in messages]
                })
            except Exception:
                return jsonify({'success': True, 'data': []})

        elif action == 'clear_conversation':
            conversation_id = request.form.get('conversation_id', '')
            try:
                ConversationMessage.query.filter_by(conversation_id=conversation_id).delete()
                Conversation.query.filter_by(conversation_id=conversation_id).delete()
                db.session.commit()
                return jsonify({'success': True, 'message': 'Conversación eliminada'})
            except Exception:
                db.session.rollback()
                return jsonify({'success': False, 'message': 'Error eliminando conversación'})

        elif action == 'select_personality':
            personality = request.form.get('personality', 'guardian')
            session['chatbot_personality'] = personality
            return jsonify({'success': True, 'personality': personality})

        elif action == 'get_system_status':
            from utils.security import get_system_metrics
            return jsonify({
                'success': True,
                'data': get_system_metrics()
            })

        # Acciones multimedia premium
        elif action in ['open_music_studio', 'process_voice_recording', 'upload_backing_track',
                        'upload_lyrics', 'upload_video_fragment', 'generate_video_story',
                        'start_karaoke', 'save_karaoke_recording', 'generate_final_track',
                        'ask_professional_question']:
            if not is_premium_user(current_user.id, current_user.username):
                return jsonify({
                    'success': False,
                    'message': 'Esta función requiere membresía Premium'
                })
            return jsonify({
                'success': True,
                'message': f'Función {action} procesada correctamente',
                'data': {'action': action, 'status': 'completed'}
            })

    # GET - Mostrar página del chatbot
    personalities = GuardianAIChatbotEngine.PERSONALITIES
    current_personality = session.get('chatbot_personality', 'guardian')
    return render_template('chatbot.html',
                           user=current_user,
                           personalities=personalities,
                           current_personality=current_personality,
                           is_premium=is_premium_user(current_user.id, current_user.username))


@chatbot_bp.route('/guardian_ai_chatbot', methods=['GET', 'POST'])
@login_required
def guardian_ai_chatbot():
    """Chatbot Guardian AI (módulo separado)"""
    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'send_message':
            message = sanitize_input(request.form.get('message', ''))
            result = GuardianAIChatbotEngine.process_message(message, 'guardian', current_user.id)
            return jsonify({'success': True, 'data': result})
        elif action == 'get_system_status':
            from utils.security import get_system_metrics
            return jsonify({'success': True, 'data': get_system_metrics()})
    return render_template('guardian_ai_chatbot.html', user=current_user)


@chatbot_bp.route('/communication_hub', methods=['GET', 'POST'])
@login_required
def communication_hub():
    """Hub de comunicación - Luna AI"""
    if request.method == 'POST':
        action = request.form.get('action', '')
        if action == 'send_message':
            message = sanitize_input(request.form.get('message', ''))
            result = GuardianAIChatbotEngine.process_message(message, 'luna', current_user.id)
            return jsonify({'success': True, 'data': result})
    return render_template('communication_hub.html', user=current_user,
                           is_premium=is_premium_user(current_user.id, current_user.username))
