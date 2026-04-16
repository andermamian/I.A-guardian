"""
GuardianIA v3.0 - Modelos de Base de Datos
"""
from flask_sqlalchemy import SQLAlchemy
from flask_login import UserMixin
from datetime import datetime
import json

db = SQLAlchemy()


class User(UserMixin, db.Model):
    __tablename__ = 'users'
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    fullname = db.Column(db.String(200), default='')
    user_type = db.Column(db.String(20), default='user')  # admin, user
    premium_status = db.Column(db.String(20), default='basic')  # basic, premium
    security_clearance = db.Column(db.String(50), default='UNCLASSIFIED')
    military_access = db.Column(db.Boolean, default=False)
    status = db.Column(db.String(20), default='active')
    login_attempts = db.Column(db.Integer, default=0)
    last_login = db.Column(db.DateTime, nullable=True)
    locked_until = db.Column(db.DateTime, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    def to_dict(self):
        return {
            'id': self.id,
            'username': self.username,
            'email': self.email,
            'fullname': self.fullname,
            'user_type': self.user_type,
            'premium_status': self.premium_status,
            'security_clearance': self.security_clearance,
            'military_access': self.military_access,
            'status': self.status,
            'last_login': self.last_login.isoformat() if self.last_login else None,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }


class SecurityEvent(db.Model):
    __tablename__ = 'security_events'
    id = db.Column(db.Integer, primary_key=True)
    event_type = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text)
    severity = db.Column(db.String(20), default='medium')
    user_id = db.Column(db.String(50))
    ip_address = db.Column(db.String(45))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class ThreatEvent(db.Model):
    __tablename__ = 'threat_events'
    id = db.Column(db.Integer, primary_key=True)
    event_id = db.Column(db.String(50), unique=True)
    user_id = db.Column(db.Integer, nullable=True)
    threat_type = db.Column(db.String(100))
    severity_level = db.Column(db.String(20))
    description = db.Column(db.Text)
    source_ip = db.Column(db.String(45))
    metadata_json = db.Column(db.Text)
    status = db.Column(db.String(20), default='active')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class AIDetection(db.Model):
    __tablename__ = 'ai_detections'
    id = db.Column(db.Integer, primary_key=True)
    detection_type = db.Column(db.String(100))
    confidence = db.Column(db.Float, default=0.0)
    description = db.Column(db.Text)
    user_id = db.Column(db.Integer, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class Conversation(db.Model):
    __tablename__ = 'conversations'
    id = db.Column(db.Integer, primary_key=True)
    conversation_id = db.Column(db.String(50), unique=True)
    user_id = db.Column(db.Integer, nullable=False)
    title = db.Column(db.String(200), default='Nueva Conversación')
    personality = db.Column(db.String(50), default='guardian')
    status = db.Column(db.String(20), default='active')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    messages = db.relationship('ConversationMessage', backref='conversation', lazy=True)


class ConversationMessage(db.Model):
    __tablename__ = 'conversation_messages'
    id = db.Column(db.Integer, primary_key=True)
    message_id = db.Column(db.String(50), unique=True)
    conversation_id = db.Column(db.String(50), db.ForeignKey('conversations.conversation_id'))
    user_id = db.Column(db.Integer)
    sender_type = db.Column(db.String(20))  # user, ai
    message_content = db.Column(db.Text)
    confidence_score = db.Column(db.Float, default=0.8)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class PerformanceMetric(db.Model):
    __tablename__ = 'performance_metrics'
    id = db.Column(db.Integer, primary_key=True)
    metric_id = db.Column(db.String(50), unique=True)
    user_id = db.Column(db.Integer, nullable=True)
    metric_type = db.Column(db.String(50))
    metric_name = db.Column(db.String(100))
    metric_value = db.Column(db.Float)
    metric_unit = db.Column(db.String(20))
    status = db.Column(db.String(20), default='normal')
    collected_at = db.Column(db.DateTime, default=datetime.utcnow)


class MembershipPlan(db.Model):
    __tablename__ = 'membership_plans'
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    price = db.Column(db.Float, default=0.0)
    duration_days = db.Column(db.Integer, default=30)
    features = db.Column(db.Text)
    status = db.Column(db.String(20), default='active')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class BackupRecord(db.Model):
    __tablename__ = 'backup_records'
    id = db.Column(db.Integer, primary_key=True)
    backup_id = db.Column(db.String(50), unique=True)
    user_id = db.Column(db.Integer)
    backup_type = db.Column(db.String(50))
    file_path = db.Column(db.String(500))
    file_size = db.Column(db.Integer)
    status = db.Column(db.String(20), default='completed')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class VaultItem(db.Model):
    __tablename__ = 'vault_items'
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, nullable=False)
    item_name = db.Column(db.String(200))
    item_type = db.Column(db.String(50))
    encrypted_data = db.Column(db.Text)
    category = db.Column(db.String(50))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)


class MusicComposition(db.Model):
    __tablename__ = 'music_compositions'
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, nullable=False)
    title = db.Column(db.String(200))
    genre = db.Column(db.String(50))
    tempo = db.Column(db.Integer, default=120)
    key_signature = db.Column(db.String(10))
    data_json = db.Column(db.Text)
    status = db.Column(db.String(20), default='draft')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)


class SystemLog(db.Model):
    __tablename__ = 'system_logs'
    id = db.Column(db.Integer, primary_key=True)
    level = db.Column(db.String(20))
    event_type = db.Column(db.String(100))
    message = db.Column(db.Text)
    user_id = db.Column(db.String(50))
    ip_address = db.Column(db.String(45))
    classification = db.Column(db.String(20), default='UNCLASSIFIED')
    context_json = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
