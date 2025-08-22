import React, { useEffect, useState } from 'react';

const AdminPage = ({ onBackToMenu }) => {
    const [tab, setTab] = useState('users');
    const [users, setUsers] = useState([]);
    const [questions, setQuestions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [page, setPage] = useState(1);
    const [limit] = useState(50);
    const token = localStorage.getItem('token');

    useEffect(() => {
        if (tab === 'users') fetchUsers();
        if (tab === 'questions') fetchQuestions();
    }, [tab]);

    const fetchUsers = async () => {
        setLoading(true); setError(null);
        try {
            const res = await fetch('/api/admin/users', { headers: { 'x-auth-token': token } });
            if (!res.ok) throw new Error('Fehler beim Laden der Nutzerliste');
            const data = await res.json(); setUsers(data);
        } catch (err) { setError(err.message); }
        setLoading(false);
    };

    const fetchQuestions = async () => {
        setLoading(true); setError(null);
        try {
            const res = await fetch(`/api/admin/questions?limit=${limit}&page=${page}`, { headers: { 'x-auth-token': token } });
            if (!res.ok) throw new Error('Fehler beim Laden der Fragen');
            const data = await res.json(); setQuestions(data);
        } catch (err) { setError(err.message); }
        setLoading(false);
    };

    // User actions
    const updateUserRole = async (id, role) => {
        try {
            await fetch(`/api/admin/users/${id}/role`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'x-auth-token': token }, body: JSON.stringify({ role }) });
            fetchUsers();
        } catch (err) { setError('Fehler beim Aktualisieren der Rolle'); }
    };

    const deleteUser = async (id) => {
        if (!window.confirm('Benutzer wirklich löschen?')) return;
        try {
            await fetch(`/api/admin/users/${id}`, { method: 'DELETE', headers: { 'x-auth-token': token } });
            fetchUsers();
        } catch (err) { setError('Fehler beim Löschen des Benutzers'); }
    };

    // Questions CRUD
    const createQuestion = async (q) => {
        try {
            await fetch('/api/admin/questions', { method: 'POST', headers: { 'Content-Type': 'application/json', 'x-auth-token': token }, body: JSON.stringify(q) });
            fetchQuestions();
        } catch (err) { setError('Fehler beim Erstellen der Frage'); }
    };

    const updateQuestion = async (id, q) => {
        try {
            await fetch(`/api/admin/questions/${id}`, { method: 'PUT', headers: { 'Content-Type': 'application/json', 'x-auth-token': token }, body: JSON.stringify(q) });
            fetchQuestions();
        } catch (err) { setError('Fehler beim Aktualisieren der Frage'); }
    };

    const deleteQuestion = async (id) => {
        if (!window.confirm('Frage wirklich löschen?')) return;
        try {
            await fetch(`/api/admin/questions/${id}`, { method: 'DELETE', headers: { 'x-auth-token': token } });
            fetchQuestions();
        } catch (err) { setError('Fehler beim Löschen der Frage'); }
    };

    // Import JSON file (array of questions)
    const handleImportJson = async (file) => {
        setError(null);
        try {
            const text = await file.text();
            const data = JSON.parse(text);
            if (!Array.isArray(data)) throw new Error('JSON muss ein Array sein');
            const res = await fetch('/api/admin/questions/import', { method: 'POST', headers: { 'Content-Type': 'application/json', 'x-auth-token': token }, body: JSON.stringify(data) });
            const body = await res.json();
            if (!res.ok) throw new Error(body.message || 'Fehler beim Import');
            alert(body.message || 'Import erfolgreich');
            fetchQuestions();
        } catch (err) { setError('Import-Fehler: ' + err.message); }
    };

    // Simple CSV parser (expects headers question_text,correct_answer,category,difficulty)
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
            await fetch('/api/admin/questions/import', { method: 'POST', headers: { 'Content-Type': 'application/json', 'x-auth-token': token }, body: JSON.stringify(items) });
            alert('CSV importiert: ' + items.length + ' Fragen');
            fetchQuestions();
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
