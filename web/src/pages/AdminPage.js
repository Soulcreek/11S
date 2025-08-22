import React, { useEffect, useState } from 'react';
import extraQuestions from '../data/extraQuestions';

const AdminPage = ({ onBackToMenu }) => {
    const [tab, setTab] = useState('users');
    const [users, setUsers] = useState([]);
    const [questions, setQuestions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [page, setPage] = useState(1);
    const [limit] = useState(50);

    useEffect(() => {
        if (tab === 'users') fetchUsers();
        if (tab === 'questions') fetchQuestions();
    }, [tab]);

    const fetchUsers = async () => {
        setLoading(true); setError(null);
        try {
            // Static data - no API calls needed
            const mockUsers = [
                { id: 1, username: 'admin', email: 'admin@11seconds.de', role: 'admin', created_at: '2025-08-22' },
                { id: 2, username: 'testuser', email: 'test@11seconds.de', role: 'user', created_at: '2025-08-22' }
            ];
            setUsers(mockUsers);
        } catch (err) { setError(err.message); }
        setLoading(false);
    };

    const fetchQuestions = async () => {
        setLoading(true); setError(null);
        try {
            // Use local questions data
            const startIndex = (page - 1) * limit;
            const endIndex = startIndex + limit;
            const paginatedQuestions = extraQuestions.slice(startIndex, endIndex);
            setQuestions(paginatedQuestions);
        } catch (err) { setError(err.message); }
        setLoading(false);
    };

    // User actions (static mode - no actual updates)
    const updateUserRole = async (id, role) => {
        try {
            // Static mode - just update local state
            setUsers(users.map(user => user.id === id ? { ...user, role } : user));
            alert(`Rolle für Benutzer ${id} auf ${role} gesetzt (nur lokale Änderung)`);
        } catch (err) { setError('Fehler beim Aktualisieren der Rolle'); }
    };

    const deleteUser = async (id) => {
        if (!window.confirm('Benutzer wirklich löschen? (nur lokale Änderung)')) return;
        try {
            // Static mode - just update local state
            setUsers(users.filter(user => user.id !== id));
        } catch (err) { setError('Fehler beim Löschen des Benutzers'); }
    };

    // Questions CRUD (static mode - local state only)
    const createQuestion = async (q) => {
        try {
            // Static mode - add to local state
            const newQuestion = { ...q, question_id: Date.now() };
            setQuestions([...questions, newQuestion]);
            alert('Frage erstellt (nur lokale Änderung)');
        } catch (err) { setError('Fehler beim Erstellen der Frage'); }
    };

    const updateQuestion = async (id, q) => {
        try {
            // Static mode - update local state
            setQuestions(questions.map(question => 
                question.question_id === id ? { ...question, ...q } : question
            ));
            alert('Frage aktualisiert (nur lokale Änderung)');
        } catch (err) { setError('Fehler beim Aktualisieren der Frage'); }
    };

    const deleteQuestion = async (id) => {
        if (!window.confirm('Frage wirklich löschen? (nur lokale Änderung)')) return;
        try {
            // Static mode - remove from local state
            setQuestions(questions.filter(question => question.question_id !== id));
            alert('Frage gelöscht (nur lokale Änderung)');
        } catch (err) { setError('Fehler beim Löschen der Frage'); }
    };

    // Import JSON file (static mode - display only)
    const handleImportJson = async (file) => {
        setError(null);
        try {
            const text = await file.text();
            const data = JSON.parse(text);
            if (!Array.isArray(data)) throw new Error('JSON muss ein Array sein');
            alert(`JSON-Datei gelesen: ${data.length} Fragen gefunden (nur Anzeige im Static-Modus)`);
            console.log('Import data:', data);
        } catch (err) { setError('Import-Fehler: ' + err.message); }
    };

    // Simple CSV parser (static mode - display only)
    const handleImportCsv = async (file) => {
        setError(null);
        try {
            const text = await file.text();
            const lines = text.split(/\r?\n/).filter(Boolean);
            const header = lines.shift().split(',').map(h => h.trim());
            const items = lines.map(line => {
                const cols = line.split(',');
                const obj = {};
                header.forEach((h, i) => { obj[h] = cols[i]; });
                return { question_text: obj.question_text, correct_answer: parseFloat(obj.correct_answer), category: obj.category || 'general', difficulty: obj.difficulty || 'medium' };
            });
            alert(`CSV-Datei gelesen: ${items.length} Fragen gefunden (nur Anzeige im Static-Modus)`);
            console.log('CSV data:', items);
        } catch (err) { setError('CSV Import Fehler: ' + err.message); }
    };

    // File input change handler
    const onFileChange = (e) => {
        const f = e.target.files && e.target.files[0];
        if (!f) return;
        if (f.name.endsWith('.json')) handleImportJson(f);
        else handleImportCsv(f);
    };

    return (
        <div style={{ padding: 20 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <h2>Admin</h2>
                <div>
                    <button onClick={onBackToMenu}>Zurück</button>
                </div>
            </div>

            <div style={{ marginTop: 10 }}>
                <button onClick={() => setTab('users')} disabled={tab === 'users'}>Users</button>
                <button onClick={() => setTab('questions')} disabled={tab === 'questions'} style={{ marginLeft: 8 }}>Questions</button>
            </div>

            {loading && <p>Lädt…</p>}
            {error && <p style={{ color: 'red' }}>{error}</p>}

            {tab === 'users' && (
                <div style={{ marginTop: 12 }}>
                    <h3>Users</h3>
                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            {users.map(u => (
                                <tr key={u.user_id} style={{ borderTop: '1px solid #ddd' }}>
                                    <td>{u.user_id}</td>
                                    <td>{u.username}</td>
                                    <td>{u.email}</td>
                                    <td>{u.role}</td>
                                    <td>{new Date(u.created_at).toLocaleString()}</td>
                                    <td>
                                        {u.role !== 'admin' ? <button onClick={() => updateUserRole(u.user_id, 'admin')}>Make Admin</button> : <button onClick={() => updateUserRole(u.user_id, 'user')}>Revoke</button>}
                                        <button onClick={() => deleteUser(u.user_id)} style={{ marginLeft: 8, color: 'red' }}>Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {tab === 'questions' && (
                <div style={{ marginTop: 12 }}>
                    <h3>Questions</h3>
                    <div style={{ marginBottom: 8 }}>
                        <input type="file" accept=".json,.csv" onChange={onFileChange} />
                        <small style={{ marginLeft: 8 }}>Upload JSON array or CSV with headers: question_text,correct_answer,category,difficulty</small>
                    </div>
                    <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                        <thead>
                            <tr><th>ID</th><th>Text</th><th>Answer</th><th>Category</th><th>Diff</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            {questions.map(q => (
                                <tr key={q.question_id} style={{ borderTop: '1px solid #eee' }}>
                                    <td>{q.question_id}</td>
                                    <td style={{ maxWidth: 400 }}>{q.question_text}</td>
                                    <td>{q.correct_answer}</td>
                                    <td>{q.category}</td>
                                    <td>{q.difficulty}</td>
                                    <td>
                                        <button onClick={() => { const newText = prompt('Edit question text', q.question_text); if (newText != null) updateQuestion(q.question_id, { ...q, question_text: newText }); }}>Edit</button>
                                        <button onClick={() => deleteQuestion(q.question_id)} style={{ marginLeft: 8, color: 'red' }}>Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
};

export default AdminPage;
