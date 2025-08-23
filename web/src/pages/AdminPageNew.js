import React, { useEffect, useState } from 'react';
import extraQuestions from '../data/extraQuestions';

const AdminPage = ({ onBackToMenu }) => {
    const [tab, setTab] = useState('dashboard');
    const [users, setUsers] = useState([]);
    const [questions, setQuestions] = useState([]);
    const [gameSettings, setGameSettings] = useState({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [page, setPage] = useState(1);
    const [limit] = useState(50);

    useEffect(() => {
        if (tab === 'dashboard') loadDashboardData();
        if (tab === 'users') fetchUsers();
        if (tab === 'questions') fetchQuestions();
        if (tab === 'settings') loadGameSettings();
    }, [tab]);

    const loadDashboardData = async () => {
        setLoading(true); setError(null);
        try {
            const storedUsers = JSON.parse(localStorage.getItem('registeredUsers') || '{}');
            const userArray = Object.values(storedUsers);
            
            const stats = {
                totalUsers: userArray.length,
                totalQuestions: extraQuestions.length,
                activeUsers: userArray.filter(u => {
                    const lastActive = new Date(u.lastLogin || u.registeredAt || 0);
                    const weekAgo = new Date();
                    weekAgo.setDate(weekAgo.getDate() - 7);
                    return lastActive > weekAgo;
                }).length,
                categories: [...new Set(extraQuestions.map(q => q.category))],
                difficulties: [...new Set(extraQuestions.map(q => q.difficulty))]
            };
            
            setUsers(stats);
        } catch (err) { 
            setError(err.message);
            console.error('Dashboard error:', err); 
        }
        setLoading(false);
    };

    const loadGameSettings = async () => {
        setLoading(true); setError(null);
        try {
            const settings = JSON.parse(localStorage.getItem('adminGameSettings') || JSON.stringify({
                allowExitDuringGame: true,
                showTimerWarning: true,
                allowSkipQuestions: false,
                enableSoundEffects: true,
                maxGameTime: 300,
                defaultDifficulty: 'all',
                defaultCategories: ['all']
            }));
            setGameSettings(settings);
        } catch (err) { 
            setError(err.message); 
        }
        setLoading(false);
    };

    const saveGameSettings = async (newSettings) => {
        try {
            localStorage.setItem('adminGameSettings', JSON.stringify(newSettings));
            setGameSettings(newSettings);
            alert('Spieleinstellungen gespeichert!');
        } catch (err) {
            setError('Fehler beim Speichern der Einstellungen');
        }
    };

    const fetchUsers = async () => {
        setLoading(true); setError(null);
        try {
            const storedUsers = JSON.parse(localStorage.getItem('registeredUsers') || '{}');
            const userArray = Object.values(storedUsers).map((user, index) => ({
                user_id: index + 1,
                username: user.username,
                email: user.email || 'Nicht angegeben',
                role: user.username === 'admin' ? 'admin' : 'user',
                created_at: user.registeredAt || new Date().toISOString(),
                loginMethod: user.loginMethod || 'password',
                lastLogin: user.lastLogin || 'Nie'
            }));
            
            const currentUsername = localStorage.getItem('username');
            const currentMethod = localStorage.getItem('loginMethod');
            if (currentUsername && !userArray.find(u => u.username === currentUsername)) {
                userArray.push({
                    user_id: userArray.length + 1,
                    username: currentUsername,
                    email: localStorage.getItem('userEmail') || 'Nicht angegeben',
                    role: currentUsername === 'admin' ? 'admin' : 'user',
                    created_at: new Date().toISOString(),
                    loginMethod: currentMethod,
                    lastLogin: 'Aktuell'
                });
            }
            
            setUsers(userArray);
        } catch (err) { 
            setError(err.message);
            console.error('Users fetch error:', err);
        }
        setLoading(false);
    };

    const fetchQuestions = async () => {
        setLoading(true); setError(null);
        try {
            const startIndex = (page - 1) * limit;
            const endIndex = startIndex + limit;
            const paginatedQuestions = extraQuestions.slice(startIndex, endIndex);
            setQuestions(paginatedQuestions);
        } catch (err) { setError(err.message); }
        setLoading(false);
    };

    const updateUserRole = async (id, role) => {
        try {
            setUsers(users.map(user => user.user_id === id ? { ...user, role } : user));
            alert(`Rolle für Benutzer ${id} auf ${role} gesetzt (nur lokale Änderung)`);
        } catch (err) { setError('Fehler beim Aktualisieren der Rolle'); }
    };

    const deleteUser = async (id) => {
        if (!window.confirm('Benutzer wirklich löschen?')) return;
        try {
            setUsers(users.filter(user => user.user_id !== id));
            alert('Benutzer gelöscht (nur lokale Änderung)');
        } catch (err) { setError('Fehler beim Löschen des Benutzers'); }
    };

    return (
        <div style={{ 
            padding: '20px', 
            fontFamily: 'Arial, sans-serif',
            background: 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)',
            minHeight: '100vh'
        }}>
            {/* Header with Back Button */}
            <div style={{ 
                display: 'flex', 
                justifyContent: 'space-between', 
                alignItems: 'center',
                background: 'white',
                padding: '15px 20px',
                borderRadius: '10px',
                boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
                marginBottom: '20px'
            }}>
                <h2 style={{ margin: 0, color: '#333' }}>🛠️ Admin Center</h2>
                <button 
                    onClick={onBackToMenu}
                    style={{
                        background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                        color: 'white',
                        border: 'none',
                        padding: '10px 20px',
                        borderRadius: '8px',
                        cursor: 'pointer',
                        fontSize: '14px',
                        fontWeight: 'bold'
                    }}
                >
                    🏠 Zurück zum Hauptmenü
                </button>
            </div>

            {/* Navigation Tabs */}
            <div style={{
                background: 'white',
                borderRadius: '10px',
                padding: '15px',
                marginBottom: '20px',
                boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
                display: 'flex',
                flexWrap: 'wrap',
                gap: '10px'
            }}>
                {['dashboard', 'users', 'questions', 'settings'].map(tabName => (
                    <button
                        key={tabName}
                        onClick={() => setTab(tabName)}
                        style={{
                            background: tab === tabName 
                                ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' 
                                : '#f8f9fa',
                            color: tab === tabName ? 'white' : '#333',
                            border: tab === tabName ? 'none' : '1px solid #ddd',
                            padding: '12px 18px',
                            borderRadius: '8px',
                            cursor: 'pointer',
                            fontSize: '14px',
                            fontWeight: tab === tabName ? 'bold' : 'normal',
                            textTransform: 'capitalize',
                            minWidth: '120px'
                        }}
                    >
                        {tabName === 'dashboard' && '📊 Dashboard'}
                        {tabName === 'users' && '👥 Benutzer'}
                        {tabName === 'questions' && '❓ Fragen'}
                        {tabName === 'settings' && '⚙️ Einstellungen'}
                    </button>
                ))}
            </div>

            {/* Loading/Error States */}
            {loading && (
                <div style={{
                    background: 'white',
                    borderRadius: '10px',
                    padding: '20px',
                    textAlign: 'center',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
                    marginBottom: '20px'
                }}>
                    <p style={{ margin: 0 }}>⏳ Lädt...</p>
                </div>
            )}
            
            {error && (
                <div style={{
                    background: '#fef2f2',
                    border: '1px solid #fca5a5',
                    borderRadius: '10px',
                    padding: '15px',
                    color: '#dc2626',
                    marginBottom: '20px'
                }}>
                    ❌ Fehler: {error}
                </div>
            )}

            {/* Dashboard Tab */}
            {tab === 'dashboard' && (
                <div style={{
                    background: 'white',
                    borderRadius: '10px',
                    padding: '25px',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.1)'
                }}>
                    <h3 style={{ marginTop: 0, color: '#333' }}>📊 System-Übersicht</h3>
                    <div style={{
                        display: 'grid',
                        gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
                        gap: '20px',
                        marginTop: '20px'
                    }}>
                        <div style={{
                            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                            color: 'white',
                            padding: '25px',
                            borderRadius: '12px',
                            textAlign: 'center'
                        }}>
                            <h4 style={{ margin: '0 0 15px 0' }}>👥 Benutzer</h4>
                            <p style={{ fontSize: '32px', margin: 0, fontWeight: 'bold' }}>
                                {users.totalUsers || 0}
                            </p>
                            <p style={{ fontSize: '14px', opacity: 0.8, margin: '8px 0 0 0' }}>
                                Davon aktiv: {users.activeUsers || 0}
                            </p>
                        </div>
                        
                        <div style={{
                            background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                            color: 'white',
                            padding: '25px',
                            borderRadius: '12px',
                            textAlign: 'center'
                        }}>
                            <h4 style={{ margin: '0 0 15px 0' }}>❓ Fragen</h4>
                            <p style={{ fontSize: '32px', margin: 0, fontWeight: 'bold' }}>
                                {users.totalQuestions || 0}
                            </p>
                            <p style={{ fontSize: '14px', opacity: 0.8, margin: '8px 0 0 0' }}>
                                Kategorien: {users.categories ? users.categories.length : 0}
                            </p>
                        </div>

                        <div style={{
                            background: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                            color: 'white',
                            padding: '25px',
                            borderRadius: '12px',
                            textAlign: 'center'
                        }}>
                            <h4 style={{ margin: '0 0 15px 0' }}>🎯 Schwierigkeit</h4>
                            <p style={{ fontSize: '16px', margin: 0 }}>
                                {users.difficulties ? users.difficulties.join(', ') : 'Lädt...'}
                            </p>
                        </div>
                    </div>

                    {users.categories && (
                        <div style={{ marginTop: '30px' }}>
                            <h4 style={{ color: '#333' }}>📚 Verfügbare Kategorien:</h4>
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '10px', marginTop: '15px' }}>
                                {users.categories.map(cat => (
                                    <span key={cat} style={{
                                        background: '#e0f2fe',
                                        padding: '8px 12px',
                                        borderRadius: '16px',
                                        fontSize: '14px',
                                        color: '#0277bd',
                                        fontWeight: '500'
                                    }}>
                                        {cat}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Settings Tab */}
            {tab === 'settings' && (
                <div style={{
                    background: 'white',
                    borderRadius: '10px',
                    padding: '25px',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.1)'
                }}>
                    <h3 style={{ marginTop: 0, color: '#333' }}>⚙️ Spieleinstellungen</h3>
                    
                    <div style={{ marginTop: '25px' }}>
                        <div style={{
                            background: '#f8f9fa',
                            padding: '20px',
                            borderRadius: '10px',
                            marginBottom: '20px'
                        }}>
                            <label style={{
                                display: 'flex',
                                alignItems: 'center',
                                marginBottom: '15px',
                                cursor: 'pointer'
                            }}>
                                <input
                                    type="checkbox"
                                    checked={gameSettings.allowExitDuringGame || false}
                                    onChange={(e) => saveGameSettings({
                                        ...gameSettings,
                                        allowExitDuringGame: e.target.checked
                                    })}
                                    style={{ 
                                        marginRight: '12px', 
                                        transform: 'scale(1.3)',
                                        accentColor: '#10b981'
                                    }}
                                />
                                <span style={{ fontWeight: 'bold', color: '#333' }}>
                                    🚪 Zurück-Button während des Spiels erlauben
                                </span>
                            </label>
                            <p style={{ 
                                fontSize: '14px', 
                                color: '#666', 
                                marginLeft: '35px', 
                                marginBottom: '0',
                                lineHeight: '1.4'
                            }}>
                                Wenn aktiviert, können Spieler jederzeit zum Hauptmenü zurückkehren
                            </p>
                        </div>

                        <div style={{
                            background: '#f8f9fa',
                            padding: '20px',
                            borderRadius: '10px',
                            marginBottom: '20px'
                        }}>
                            <label style={{
                                display: 'flex',
                                alignItems: 'center',
                                marginBottom: '15px',
                                cursor: 'pointer'
                            }}>
                                <input
                                    type="checkbox"
                                    checked={gameSettings.showTimerWarning || false}
                                    onChange={(e) => saveGameSettings({
                                        ...gameSettings,
                                        showTimerWarning: e.target.checked
                                    })}
                                    style={{ 
                                        marginRight: '12px', 
                                        transform: 'scale(1.3)',
                                        accentColor: '#10b981'
                                    }}
                                />
                                <span style={{ fontWeight: 'bold', color: '#333' }}>
                                    ⏰ Timer-Warnungen anzeigen
                                </span>
                            </label>
                            
                            <label style={{
                                display: 'flex',
                                alignItems: 'center',
                                marginBottom: '15px',
                                cursor: 'pointer'
                            }}>
                                <input
                                    type="checkbox"
                                    checked={gameSettings.enableSoundEffects || false}
                                    onChange={(e) => saveGameSettings({
                                        ...gameSettings,
                                        enableSoundEffects: e.target.checked
                                    })}
                                    style={{ 
                                        marginRight: '12px', 
                                        transform: 'scale(1.3)',
                                        accentColor: '#10b981'
                                    }}
                                />
                                <span style={{ fontWeight: 'bold', color: '#333' }}>
                                    🔊 Sound-Effekte aktivieren
                                </span>
                            </label>
                        </div>

                        <div style={{ 
                            marginTop: '25px', 
                            padding: '20px', 
                            background: '#f0fdf4', 
                            borderRadius: '10px',
                            border: '1px solid #bbf7d0'
                        }}>
                            <h4 style={{ marginTop: 0, color: '#166534' }}>🎯 Standard-Einstellungen</h4>
                            
                            <label style={{ display: 'block', marginBottom: '15px' }}>
                                <span style={{ fontWeight: 'bold', color: '#333' }}>Standard-Schwierigkeit:</span>
                                <select
                                    value={gameSettings.defaultDifficulty || 'all'}
                                    onChange={(e) => saveGameSettings({
                                        ...gameSettings,
                                        defaultDifficulty: e.target.value
                                    })}
                                    style={{
                                        marginLeft: '15px',
                                        padding: '8px 12px',
                                        borderRadius: '6px',
                                        border: '1px solid #d1d5db',
                                        fontSize: '14px'
                                    }}
                                >
                                    <option value="all">Alle</option>
                                    <option value="easy">Leicht</option>
                                    <option value="medium">Mittel</option>
                                    <option value="hard">Schwer</option>
                                </select>
                            </label>

                            <label style={{ display: 'block', marginBottom: '10px' }}>
                                <span style={{ fontWeight: 'bold', color: '#333' }}>Maximale Spielzeit (Sekunden):</span>
                                <input
                                    type="number"
                                    value={gameSettings.maxGameTime || 300}
                                    onChange={(e) => saveGameSettings({
                                        ...gameSettings,
                                        maxGameTime: parseInt(e.target.value)
                                    })}
                                    style={{
                                        marginLeft: '15px',
                                        padding: '8px 12px',
                                        borderRadius: '6px',
                                        border: '1px solid #d1d5db',
                                        width: '100px',
                                        fontSize: '14px'
                                    }}
                                />
                            </label>
                        </div>
                    </div>
                </div>
            )}

            {/* Users Tab */}
            {tab === 'users' && (
                <div style={{
                    background: 'white',
                    borderRadius: '10px',
                    padding: '25px',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.1)'
                }}>
                    <h3 style={{ marginTop: 0, color: '#333' }}>👥 Benutzerverwaltung</h3>
                    <div style={{ overflowX: 'auto', marginTop: '20px' }}>
                        <table style={{ 
                            width: '100%', 
                            borderCollapse: 'collapse',
                            minWidth: '600px'
                        }}>
                            <thead>
                                <tr style={{ background: '#f8f9fa' }}>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef' }}>ID</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef' }}>Username</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef' }}>Email</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef' }}>Rolle</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef' }}>Methode</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef' }}>Erstellt</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef' }}>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                {Array.isArray(users) && users.map(u => (
                                    <tr key={u.user_id} style={{ borderBottom: '1px solid #e9ecef' }}>
                                        <td style={{ padding: '12px' }}>{u.user_id}</td>
                                        <td style={{ padding: '12px', fontWeight: 'bold' }}>{u.username}</td>
                                        <td style={{ padding: '12px' }}>{u.email}</td>
                                        <td style={{ padding: '12px' }}>
                                            <span style={{
                                                background: u.role === 'admin' ? '#dc2626' : '#10b981',
                                                color: 'white',
                                                padding: '4px 8px',
                                                borderRadius: '12px',
                                                fontSize: '12px'
                                            }}>
                                                {u.role}
                                            </span>
                                        </td>
                                        <td style={{ padding: '12px' }}>{u.loginMethod}</td>
                                        <td style={{ padding: '12px', fontSize: '12px' }}>
                                            {new Date(u.created_at).toLocaleString('de-DE')}
                                        </td>
                                        <td style={{ padding: '12px' }}>
                                            <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                                                {u.role !== 'admin' ? 
                                                    <button 
                                                        onClick={() => updateUserRole(u.user_id, 'admin')}
                                                        style={{
                                                            background: '#10b981',
                                                            color: 'white',
                                                            border: 'none',
                                                            padding: '6px 12px',
                                                            borderRadius: '6px',
                                                            fontSize: '12px',
                                                            cursor: 'pointer'
                                                        }}
                                                    >
                                                        Admin machen
                                                    </button>
                                                    : 
                                                    <button 
                                                        onClick={() => updateUserRole(u.user_id, 'user')}
                                                        style={{
                                                            background: '#f59e0b',
                                                            color: 'white',
                                                            border: 'none',
                                                            padding: '6px 12px',
                                                            borderRadius: '6px',
                                                            fontSize: '12px',
                                                            cursor: 'pointer'
                                                        }}
                                                    >
                                                        Zurückstufen
                                                    </button>
                                                }
                                                <button 
                                                    onClick={() => deleteUser(u.user_id)}
                                                    style={{
                                                        background: '#dc2626',
                                                        color: 'white',
                                                        border: 'none',
                                                        padding: '6px 12px',
                                                        borderRadius: '6px',
                                                        fontSize: '12px',
                                                        cursor: 'pointer'
                                                    }}
                                                >
                                                    Löschen
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {Array.isArray(users) && users.length === 0 && (
                            <div style={{ textAlign: 'center', padding: '40px', color: '#666' }}>
                                Keine Benutzer gefunden
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Questions Tab */}
            {tab === 'questions' && (
                <div style={{
                    background: 'white',
                    borderRadius: '10px',
                    padding: '25px',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.1)'
                }}>
                    <h3 style={{ marginTop: 0, color: '#333' }}>❓ Fragenverwaltung</h3>
                    <p style={{ color: '#666', marginBottom: '25px' }}>
                        Hier können Sie die verfügbaren Quiz-Fragen einsehen und verwalten.
                    </p>
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ 
                            width: '100%', 
                            borderCollapse: 'collapse',
                            minWidth: '800px'
                        }}>
                            <thead>
                                <tr style={{ background: '#f8f9fa' }}>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef', width: '60px' }}>ID</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef' }}>Frage</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef', width: '120px' }}>Antwort</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef', width: '100px' }}>Kategorie</th>
                                    <th style={{ padding: '12px', textAlign: 'left', borderBottom: '2px solid #e9ecef', width: '80px' }}>Schwierigkeit</th>
                                </tr>
                            </thead>
                            <tbody>
                                {questions.map(q => (
                                    <tr key={q.question_id} style={{ borderBottom: '1px solid #e9ecef' }}>
                                        <td style={{ padding: '12px' }}>{q.question_id}</td>
                                        <td style={{ padding: '12px', maxWidth: '300px', lineHeight: '1.4' }}>
                                            {q.question_text}
                                        </td>
                                        <td style={{ padding: '12px', fontWeight: 'bold', color: '#10b981' }}>
                                            {q.correct_answer}
                                        </td>
                                        <td style={{ padding: '12px' }}>
                                            <span style={{
                                                background: '#e0f2fe',
                                                color: '#0277bd',
                                                padding: '4px 8px',
                                                borderRadius: '12px',
                                                fontSize: '12px'
                                            }}>
                                                {q.category}
                                            </span>
                                        </td>
                                        <td style={{ padding: '12px' }}>
                                            <span style={{
                                                background: q.difficulty === 'easy' ? '#dcfce7' : 
                                                          q.difficulty === 'medium' ? '#fef3c7' : '#fee2e2',
                                                color: q.difficulty === 'easy' ? '#166534' : 
                                                       q.difficulty === 'medium' ? '#92400e' : '#dc2626',
                                                padding: '4px 8px',
                                                borderRadius: '12px',
                                                fontSize: '12px'
                                            }}>
                                                {q.difficulty}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        
                        {questions.length === 0 && (
                            <div style={{ textAlign: 'center', padding: '40px', color: '#666' }}>
                                Keine Fragen gefunden
                            </div>
                        )}

                        <div style={{ 
                            marginTop: '20px', 
                            textAlign: 'center',
                            padding: '15px',
                            background: '#f8f9fa',
                            borderRadius: '8px'
                        }}>
                            <p style={{ margin: '0 0 10px 0', color: '#666' }}>
                                Zeige {questions.length} von {extraQuestions.length} Fragen
                            </p>
                            <div style={{ display: 'flex', justifyContent: 'center', gap: '10px' }}>
                                <button
                                    onClick={() => setPage(Math.max(1, page - 1))}
                                    disabled={page === 1}
                                    style={{
                                        padding: '8px 16px',
                                        border: '1px solid #ddd',
                                        borderRadius: '6px',
                                        background: page === 1 ? '#f8f9fa' : 'white',
                                        cursor: page === 1 ? 'default' : 'pointer'
                                    }}
                                >
                                    ← Vorherige
                                </button>
                                <span style={{ padding: '8px 16px', color: '#666' }}>
                                    Seite {page}
                                </span>
                                <button
                                    onClick={() => setPage(page + 1)}
                                    disabled={(page * limit) >= extraQuestions.length}
                                    style={{
                                        padding: '8px 16px',
                                        border: '1px solid #ddd',
                                        borderRadius: '6px',
                                        background: (page * limit) >= extraQuestions.length ? '#f8f9fa' : 'white',
                                        cursor: (page * limit) >= extraQuestions.length ? 'default' : 'pointer'
                                    }}
                                >
                                    Nächste →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default AdminPage;
