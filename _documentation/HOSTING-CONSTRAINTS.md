# 11 SECONDS PROJECT - PERMANENT CONSTRAINTS

## ⚠️ CRITICAL DEPLOYMENT CONSTRAINT ⚠️

**NO BACKEND SERVER - STATIC HOSTING ONLY**

### What This Means:

- ❌ NO Node.js server
- ❌ NO Express.js endpoints
- ❌ NO /api/\* routes
- ❌ NO server-side JavaScript
- ❌ NO backend processes

### What We CAN Use:

- ✅ PHP files (your hosting supports PHP)
- ✅ Direct database connections from PHP
- ✅ Static React build files
- ✅ Client-side JavaScript only
- ✅ MySQL database (direct PHP connection)

### For Authentication (Google OAuth):

**SOLUTION**: Use Google OAuth with client-side only implementation:

1. Google Sign-In JavaScript SDK (client-side)
2. Direct PHP endpoints for user management
3. Store user data in MySQL via PHP
4. Session management via PHP only

### For User Registration:

**SOLUTION**:

1. Client-side form validation
2. Direct PHP form processing
3. MySQL user storage via PHP

## 🚨 REMINDER FOR AI ASSISTANT 🚨

**BEFORE SUGGESTING ANY FEATURE:**

1. Can this work with static hosting only?
2. Does this require a permanent server process?
3. Can this be implemented with PHP + MySQL + client-side JS only?

**IF ANY ANSWER IS "NO" - DO NOT SUGGEST IT!**

---

This constraint exists because:

- Netcup hosting is shared hosting (no permanent Node.js processes)
- Budget constraints (no VPS/dedicated server)
- Maintenance simplicity (no server management)
- Deployment simplicity (FTP upload only)
