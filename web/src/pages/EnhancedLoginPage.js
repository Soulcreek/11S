import React, { useState, useEffect } from 'react';
import useAuth from '../hooks/useAuth';

const EnhancedLoginPage = ({ onLoginSuccess, onBack }) => {
  const auth = useAuth();
  const [activeTab, setActiveTab] = useState('guest');
  const [formData, setFormData] = useState({
    email: '',
    phone: '',
    password: '',
    confirmPassword: '',
    username: '',
    loginIdentifier: '',
    loginPassword: '',
    verificationCode: '',
    verificationMethod: 'email'
  });

  useEffect(() => {
    auth.initGoogleAuth();
    
    // Render Google Sign-In button
    if (window.google && activeTab === 'login') {
      window.google.accounts.id.renderButton(
        document.getElementById('google-signin-button'),
        {
          theme: 'outline',
          size: 'large',
          width: '100%',
          text: 'signin_with',
          shape: 'rectangular'
        }
      );
    }
  }, [activeTab]);

  useEffect(() => {
    if (auth.user) {
      onLoginSuccess(auth.user);
    }
  }, [auth.user, onLoginSuccess]);

  const handleInputChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const validatePassword = (password) => {
    const errors = [];
    if (password.length < 8) errors.push('Mindestens 8 Zeichen');
    if (!/[A-Z]/.test(password)) errors.push('Ein Großbuchstabe');
    if (!/[a-z]/.test(password)) errors.push('Ein Kleinbuchstabe');
    if (!/[0-9]/.test(password)) errors.push('Eine Zahl');
    if (!/[^A-Za-z0-9]/.test(password)) errors.push('Ein Sonderzeichen');
    return errors;
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    auth.setError('');
    
    // Validation
    if (!formData.email && !formData.phone) {
      auth.setError('E-Mail oder Telefonnummer ist erforderlich');
      return;
    }
    
    if (formData.password !== formData.confirmPassword) {
      auth.setError('Passwörter stimmen nicht überein');
      return;
    }
    
    const passwordErrors = validatePassword(formData.password);
    if (passwordErrors.length > 0) {
      auth.setError('Passwort Anforderungen: ' + passwordErrors.join(', '));
      return;
    }
    
    await auth.handleRegister({
      email: formData.email || null,
      phone: formData.phone || null,
      password: formData.password,
      username: formData.username || null,
      verification_method: formData.verificationMethod
    });
  };

  const handleLogin = async (e) => {
    e.preventDefault();
    auth.setError('');
    
    if (!formData.loginIdentifier || !formData.loginPassword) {
      auth.setError('Bitte alle Felder ausfüllen');
      return;
    }
    
    await auth.handleLogin({
      identifier: formData.loginIdentifier,
      password: formData.loginPassword
    });
  };

  const handleVerification = async (e) => {
    e.preventDefault();
    auth.setError('');
    
    if (!formData.verificationCode || formData.verificationCode.length !== 6) {
      auth.setError('Bitte 6-stelligen Code eingeben');
      return;
    }
    
    await auth.handleVerification(formData.verificationCode);
  };

  const styles = {
    container: {
      minHeight: '100vh',
      background: 'linear-gradient(135deg, #2ECC71 0%, #27AE60 100%)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      padding: '20px'
    },
    loginContainer: {
      background: 'white',
      padding: '40px',
      borderRadius: '20px',
      boxShadow: '0 20px 60px rgba(0,0,0,0.2)',
      width: '100%',
      maxWidth: '500px',
      textAlign: 'center'
    },
    logo: {
      fontSize: '48px',
      marginBottom: '10px'
    },
    title: {
      fontSize: '28px',
      fontWeight: 'bold',
      background: 'linear-gradient(45deg, #2ECC71, #27AE60)',
      backgroundClip: 'text',
      WebkitBackgroundClip: 'text',
      color: 'transparent',
      marginBottom: '30px'
    },
    tabs: {
      display: 'flex',
      marginBottom: '30px',
      borderBottom: '2px solid #eee'
    },
    tab: {
      flex: 1,
      padding: '15px',
      border: 'none',
      background: 'none',
      cursor: 'pointer',
      fontSize: '16px',
      fontWeight: 'bold',
      color: '#666',
      borderBottom: '2px solid transparent',
      transition: 'all 0.3s ease'
    },
    tabActive: {
      color: '#2ECC71',
      borderBottomColor: '#2ECC71'
    },
    formGroup: {
      marginBottom: '20px',
      textAlign: 'left'
    },
    label: {
      display: 'block',
      marginBottom: '8px',
      fontWeight: 'bold',
      color: '#333'
    },
    input: {
      width: '100%',
      padding: '15px',
      border: '2px solid #ddd',
      borderRadius: '10px',
      fontSize: '16px',
      transition: 'border-color 0.3s ease'
    },
    inputFocus: {
      borderColor: '#2ECC71',
      outline: 'none'
    },
    button: {
      width: '100%',
      padding: '15px',
      background: 'linear-gradient(45deg, #2ECC71, #27AE60)',
      color: 'white',
      border: 'none',
      borderRadius: '10px',
      fontSize: '16px',
      fontWeight: 'bold',
      cursor: 'pointer',
      transition: 'transform 0.2s ease',
      marginBottom: '15px'
    },
    buttonHover: {
      transform: 'translateY(-2px)'
    },
    buttonSecondary: {
      background: 'linear-gradient(45deg, #1ABC9C, #16A085)',
      marginBottom: '10px'
    },
    guestButton: {
      background: 'linear-gradient(45deg, #95A5A6, #7F8C8D)',
      marginBottom: '10px'
    },
    message: {
      padding: '15px',
      marginBottom: '20px',
      borderRadius: '8px',
      fontWeight: 'bold'
    },
    success: {
      background: 'rgba(39, 174, 96, 0.1)',
      color: '#27AE60',
      border: '1px solid #27AE60'
    },
    error: {
      background: 'rgba(231, 76, 60, 0.1)',
      color: '#E74C3C',
      border: '1px solid #E74C3C'
    },
    divider: {
      margin: '20px 0',
      textAlign: 'center',
      color: '#666',
      position: 'relative'
    },
    dividerLine: {
      position: 'absolute',
      top: '50%',
      left: 0,
      right: 0,
      height: '1px',
      background: '#ddd',
      zIndex: 1
    },
    dividerText: {
      background: 'white',
      padding: '0 15px',
      position: 'relative',
      zIndex: 2
    },
    passwordRequirements: {
      fontSize: '12px',
      color: '#666',
      marginTop: '5px',
      textAlign: 'left'
    },
    toggleButtons: {
      display: 'flex',
      gap: '10px',
      marginBottom: '20px'
    },
    toggleButton: {
      flex: 1,
      padding: '10px',
      border: '2px solid #ddd',
      background: 'white',
      borderRadius: '8px',
      cursor: 'pointer',
      fontSize: '14px',
      transition: 'all 0.3s ease'
    },
    toggleButtonActive: {
      borderColor: '#2ECC71',
      background: '#2ECC71',
      color: 'white'
    },
    backButton: {
      position: 'absolute',
      top: '20px',
      left: '20px',
      background: 'rgba(255,255,255,0.2)',
      color: 'white',
      border: 'none',
      borderRadius: '50%',
      width: '50px',
      height: '50px',
      fontSize: '20px',
      cursor: 'pointer',
      transition: 'all 0.3s ease'
    },
    guestInfo: {
      background: 'rgba(46, 204, 113, 0.1)',
      padding: '20px',
      borderRadius: '10px',
      marginBottom: '20px',
      border: '1px solid #2ECC71'
    },
    verificationCode: {
      fontSize: '24px',
      letterSpacing: '5px',
      textAlign: 'center',
      fontFamily: 'monospace'
    }
  };

  return (
    <div style={styles.container}>
      <button style={styles.backButton} onClick={onBack}>
        ←
      </button>
      
      <div style={styles.loginContainer}>
        <div style={styles.logo}>🎯</div>
        <h1 style={styles.title}>11Seconds Quiz</h1>
        
        {auth.error && (
          <div style={{...styles.message, ...styles.error}}>
            {auth.error}
          </div>
        )}
        
        {auth.success && (
          <div style={{...styles.message, ...styles.success}}>
            {auth.success}
          </div>
        )}

        {/* Guest Mode Active */}
        {auth.authMode === 'guest_active' && auth.guestUser && (
          <div style={styles.guestInfo}>
            <h3>Willkommen {auth.guestUser.username}!</h3>
            <p>Du spielst als Gast. Dein Fortschritt wird {Math.round((auth.guestUser.expires_at - Date.now()) / 60000)} Minuten gespeichert.</p>
            <button 
              style={{...styles.button, ...styles.buttonSecondary}}
              onClick={auth.convertGuestToUser}
            >
              Jetzt registrieren und Fortschritt dauerhaft speichern
            </button>
            <button style={styles.button} onClick={() => onLoginSuccess(auth.guestUser)}>
              Als Gast weiterspielen
            </button>
          </div>
        )}

        {/* Verification Mode */}
        {auth.authMode === 'verify' && (
          <form onSubmit={handleVerification}>
            <h3>Bestätigungscode eingeben</h3>
            <p style={{color: '#666', marginBottom: '20px'}}>
              Wir haben dir einen 6-stelligen Code per {formData.verificationMethod === 'email' ? 'E-Mail' : 'SMS'} gesendet.
            </p>
            
            <div style={styles.formGroup}>
              <input
                type="text"
                name="verificationCode"
                placeholder="123456"
                value={formData.verificationCode}
                onChange={handleInputChange}
                style={{...styles.input, ...styles.verificationCode}}
                maxLength="6"
                pattern="[0-9]{6}"
              />
            </div>
            
            <button 
              type="submit" 
              style={styles.button}
              disabled={auth.loading}
            >
              {auth.loading ? 'Bestätige...' : 'Bestätigen'}
            </button>
            
            <button 
              type="button"
              style={{...styles.button, ...styles.buttonSecondary}}
              onClick={() => auth.setAuthMode('register')}
            >
              Zurück zur Registrierung
            </button>
          </form>
        )}

        {/* Normal Auth Modes */}
        {['guest', 'login', 'register'].includes(auth.authMode) && (
          <>
            <div style={styles.tabs}>
              <button 
                style={{
                  ...styles.tab,
                  ...(activeTab === 'guest' ? styles.tabActive : {})
                }}
                onClick={() => setActiveTab('guest')}
              >
                Als Gast spielen
              </button>
              <button 
                style={{
                  ...styles.tab,
                  ...(activeTab === 'login' ? styles.tabActive : {})
                }}
                onClick={() => setActiveTab('login')}
              >
                Anmelden
              </button>
              <button 
                style={{
                  ...styles.tab,
                  ...(activeTab === 'register' ? styles.tabActive : {})
                }}
                onClick={() => setActiveTab('register')}
              >
                Registrieren
              </button>
            </div>

            {/* Guest Tab */}
            {activeTab === 'guest' && (
              <div>
                <div style={styles.guestInfo}>
                  <h3>🎮 Schnell losspielen</h3>
                  <p>Starte sofort ohne Registrierung. Dein Fortschritt wird 2 Stunden gespeichert.</p>
                  <ul style={{textAlign: 'left', margin: '15px 0'}}>
                    <li>Sofortiger Spielstart</li>
                    <li>Temporärer Fortschritt</li>
                    <li>Jederzeit registrieren möglich</li>
                  </ul>
                </div>
                
                <button 
                  style={{...styles.button, ...styles.guestButton}}
                  onClick={auth.startGuestMode}
                  disabled={auth.loading}
                >
                  {auth.loading ? 'Erstelle Gastkonto...' : '🚀 Jetzt als Gast spielen'}
                </button>
                
                <div style={styles.divider}>
                  <div style={styles.dividerLine}></div>
                  <span style={styles.dividerText}>oder</span>
                </div>
                
                <p style={{color: '#666', fontSize: '14px'}}>
                  Für dauerhafte Speicherung und Bestenlisten registriere dich kostenlos!
                </p>
              </div>
            )}

            {/* Login Tab */}
            {activeTab === 'login' && (
              <div>
                {/* Google Sign-In */}
                <div id="google-signin-button" style={{marginBottom: '20px'}}></div>
                
                <div style={styles.divider}>
                  <div style={styles.dividerLine}></div>
                  <span style={styles.dividerText}>oder</span>
                </div>
                
                <form onSubmit={handleLogin}>
                  <div style={styles.formGroup}>
                    <label style={styles.label}>E-Mail oder Benutzername:</label>
                    <input
                      type="text"
                      name="loginIdentifier"
                      value={formData.loginIdentifier}
                      onChange={handleInputChange}
                      style={styles.input}
                      required
                    />
                  </div>
                  
                  <div style={styles.formGroup}>
                    <label style={styles.label}>Passwort:</label>
                    <input
                      type="password"
                      name="loginPassword"
                      value={formData.loginPassword}
                      onChange={handleInputChange}
                      style={styles.input}
                      required
                    />
                  </div>
                  
                  <button 
                    type="submit" 
                    style={styles.button}
                    disabled={auth.loading}
                  >
                    {auth.loading ? 'Melde an...' : '🔐 Anmelden'}
                  </button>
                </form>
                
                <p style={{color: '#666', fontSize: '14px', marginTop: '15px'}}>
                  Noch kein Konto? Wechsle zur Registrierung!
                </p>
              </div>
            )}

            {/* Register Tab */}
            {activeTab === 'register' && (
              <div>
                <form onSubmit={handleRegister}>
                  {/* Verification Method Selection */}
                  <div style={styles.formGroup}>
                    <label style={styles.label}>Bestätigung per:</label>
                    <div style={styles.toggleButtons}>
                      <button
                        type="button"
                        style={{
                          ...styles.toggleButton,
                          ...(formData.verificationMethod === 'email' ? styles.toggleButtonActive : {})
                        }}
                        onClick={() => setFormData({...formData, verificationMethod: 'email'})}
                      >
                        📧 E-Mail
                      </button>
                      <button
                        type="button"
                        style={{
                          ...styles.toggleButton,
                          ...(formData.verificationMethod === 'sms' ? styles.toggleButtonActive : {})
                        }}
                        onClick={() => setFormData({...formData, verificationMethod: 'sms'})}
                      >
                        📱 SMS
                      </button>
                    </div>
                  </div>

                  {/* Email or Phone */}
                  {formData.verificationMethod === 'email' ? (
                    <div style={styles.formGroup}>
                      <label style={styles.label}>E-Mail-Adresse: *</label>
                      <input
                        type="email"
                        name="email"
                        value={formData.email}
                        onChange={handleInputChange}
                        style={styles.input}
                        required
                      />
                    </div>
                  ) : (
                    <div style={styles.formGroup}>
                      <label style={styles.label}>Telefonnummer: *</label>
                      <input
                        type="tel"
                        name="phone"
                        placeholder="+49 123 456 789"
                        value={formData.phone}
                        onChange={handleInputChange}
                        style={styles.input}
                        required
                      />
                    </div>
                  )}

                  {/* Username (Optional) */}
                  <div style={styles.formGroup}>
                    <label style={styles.label}>Benutzername (optional):</label>
                    <input
                      type="text"
                      name="username"
                      value={formData.username}
                      onChange={handleInputChange}
                      style={styles.input}
                      placeholder="Leer lassen für automatischen Namen"
                    />
                  </div>

                  {/* Password */}
                  <div style={styles.formGroup}>
                    <label style={styles.label}>Passwort: *</label>
                    <input
                      type="password"
                      name="password"
                      value={formData.password}
                      onChange={handleInputChange}
                      style={styles.input}
                      required
                    />
                    <div style={styles.passwordRequirements}>
                      Mindestens 8 Zeichen, 1 Groß-, 1 Kleinbuchstabe, 1 Zahl, 1 Sonderzeichen
                    </div>
                  </div>

                  {/* Confirm Password */}
                  <div style={styles.formGroup}>
                    <label style={styles.label}>Passwort bestätigen: *</label>
                    <input
                      type="password"
                      name="confirmPassword"
                      value={formData.confirmPassword}
                      onChange={handleInputChange}
                      style={styles.input}
                      required
                    />
                  </div>

                  <button 
                    type="submit" 
                    style={styles.button}
                    disabled={auth.loading}
                  >
                    {auth.loading ? 'Registriere...' : '📝 Registrieren'}
                  </button>
                </form>

                <div style={styles.divider}>
                  <div style={styles.dividerLine}></div>
                  <span style={styles.dividerText}>oder</span>
                </div>

                {/* Google Sign-Up */}
                <div id="google-signin-button"></div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default EnhancedLoginPage;
