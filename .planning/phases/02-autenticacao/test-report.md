# Test Report — Phase 2: Autenticação

**Date:** 2026-07-19
**Suite:** Backend Feature Tests (PHPUnit)
**Total:** 18 tests, 47 assertions
**Result:** ✅ All Pass

## Results

### LoginTest (4 tests)
| Test | Status |
|------|--------|
| login with valid credentials returns 200 and user | ✅ |
| login with wrong password returns 422 | ✅ |
| login with unverified email returns 403 | ✅ |
| remember me generates remember token | ✅ |

### LogoutTest (2 tests)
| Test | Status |
|------|--------|
| logout invalidates session | ✅ |
| logout without authentication returns 401 | ✅ |

### PasswordResetTest (5 tests)
| Test | Status |
|------|--------|
| forgot password sends reset email | ✅ |
| forgot password with nonexistent email returns generic success | ✅ |
| reset password with valid token updates password | ✅ |
| reset password with invalid token fails | ✅ |
| reset password with mismatched passwords fails | ✅ |

### RegisterTest (3 tests)
| Test | Status |
|------|--------|
| register creates user sends verification returns 201 | ✅ |
| register with duplicate email returns 422 | ✅ |
| register with mismatched passwords returns 422 | ✅ |

### VerifyEmailTest (4 tests)
| Test | Status |
|------|--------|
| valid verification link marks email as verified | ✅ |
| invalid hash returns 403 | ✅ |
| already verified user returns 200 | ✅ |
| resend verification sends new notification | ✅ |
