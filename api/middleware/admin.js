// File: api/middleware/admin.js
// Description: Middleware to ensure the user has admin privileges

const jwt = require('jsonwebtoken');

module.exports = function (req, res, next) {
    const token = req.header('x-auth-token');
    if (!token) {
        return res.status(401).json({ message: 'No token, authorization denied.' });
    }

    try {
        const decoded = jwt.verify(token, process.env.JWT_SECRET);
        req.user = decoded.user;
        if (!req.user) return res.status(401).json({ message: 'Invalid token.' });
        if (req.user.role !== 'admin') return res.status(403).json({ message: 'Admin access required.' });
        next();
    } catch (err) {
        res.status(401).json({ message: 'Token is not valid.' });
    }
};
