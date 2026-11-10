import avatar from './wgs/avatar.js';
import alerts from './wgs/alerts.js';
import password from './wgs/password.js';
import submit from './wgs/submit.js';
import shortcuts from './wgs/shortcuts.js';

[avatar, alerts, password, submit, shortcuts].forEach(fn => fn());
