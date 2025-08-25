# 🎨 Design System Documentation - 11Seconds Project

## ✅ UNIFIED LIGHT GREEN THEME (2025)

### **Primary Color Palette**

- **Primary Green**: `#10b981` (Emerald 500)
- **Secondary Green**: `#059669` (Emerald 600)
- **Accent Color**: `#1ABC9C` (Teal)

### **Gradient Definitions**

- **Main Gradient**: `linear-gradient(135deg, #10b981 0%, #059669 100%)`
- **Alternative Gradient**: `linear-gradient(45deg, #10b981, #059669)`

### **Typography Colors**

- **Text on Light**: `#333333`
- **Text on Dark**: `#ffffff`
- **Accent Text**: `#10b981`

---

## 🔥 LEGACY THEME CLEANUP (COMPLETED)

### **OLD PURPLE THEME (REMOVED):**

- ~~#667eea~~ → **#10b981** ✅
- ~~#764ba2~~ → **#059669** ✅

### **Files Updated:**

- **Admin PHP Files**: `admin/*.php` (all instances converted)
- **React Components**: `web/src/components/*.js` (all instances converted)
- **React Pages**: `web/src/pages/*.js` (all instances converted)
- **Media Management**: Default colors updated to new theme

---

## 🏗️ IMPLEMENTATION STATUS

### ✅ **COMPLETED**

- [x] Admin Center unified with light green theme
- [x] React SPA unified with light green theme
- [x] All purple theme references removed
- [x] Media/Design editor defaults updated
- [x] Multiplayer components updated
- [x] Settings and menu pages updated

### 🎯 **RESULT**

**Single, unified light green design system across:**

- Main SPA (React)
- Admin Center (PHP)
- Media Management Editor
- All multiplayer functionality
- All settings and configuration

---

## 📝 USAGE GUIDELINES

### **For New Components:**

```css
/* Headers and primary elements */
background: linear-gradient(135deg, #10b981 0%, #059669 100%);

/* Accent colors */
color: #10b981;
border-color: #10b981;

/* Interactive states */
.element:hover {
  background: #10b981;
}
```

### **For PHP Admin Templates:**

```php
$config['branding'] = [
    'primary_color' => '#10b981',
    'secondary_color' => '#059669',
    'accent_color' => '#1ABC9C'
];
```

---

## 🚫 FORBIDDEN COLORS

### **DO NOT USE:**

- ❌ `#667eea` (old purple primary)
- ❌ `#764ba2` (old purple secondary)
- ❌ `#2ECC71` (legacy green)
- ❌ `#27AE60` (legacy green secondary)

**All instances have been systematically removed from codebase.**

---

## 🔄 MAINTENANCE

**When adding new features:**

1. Always use the unified light green palette
2. Reference this document for exact color codes
3. Test across both React SPA and Admin Center
4. Ensure visual consistency

**Last Updated**: August 2025
**Status**: ✅ UNIFIED DESIGN SYSTEM ACTIVE
