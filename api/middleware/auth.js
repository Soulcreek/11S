// File: api/middleware/auth.js
// Description: Middleware to verify JSON Web Tokens.

const jwt = require('jsonwebtoken');

module.exports = function(req, res, next) {
    // Get token from the header
    const token = req.header('x-auth-token');

    // Check if not token
    if (!token) {
        return res.status(401).json({ message: 'No token, authorization denied.' });
    }

    // Verify token
    try {
        const decoded = jwt.verify(token, process.env.JWT_SECRET);
        // Add the user payload from the token to the request object
        req.user = decoded.user;
        next(); // Move to the next piece of middleware/route handler
    } catch (err) {
        res.status(401).json({ message: 'Token is not valid.' });
    }
};
