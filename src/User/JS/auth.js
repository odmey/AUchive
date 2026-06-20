// window.AUchiveAuth = (function () {
//     const USERS_KEY = "auchive_users";
//     const SESSION_KEY = "auchive_session";

//     function normalizeEmail(email) {
//         return String(email || "").trim().toLowerCase();
//     }

//     function isValidEmail(email) {
//         return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).trim());
//     }

//     function getUsers() {
//         try {
//             return JSON.parse(localStorage.getItem(USERS_KEY)) || [];
//         } catch {
//             return [];
//         }
//     }

//     function saveUsers(users) {
//         localStorage.setItem(USERS_KEY, JSON.stringify(users));
//     }

//     function getSession() {
//         try {
//             return JSON.parse(sessionStorage.getItem(SESSION_KEY)) || null;
//         } catch {
//             return null;
//         }
//     }

//     function saveSession(user) {
//         sessionStorage.setItem(
//             SESSION_KEY,
//             JSON.stringify({
//                 isLoggedIn: true,
//                 username: user.username,
//                 email: user.email,
//                 loginAt: new Date().toISOString()
//             })
//         );
//     }

//     function clearSession() {
//         sessionStorage.removeItem(SESSION_KEY);
//     }

//     function isLoggedIn() {
//         const session = getSession();
//         return !!(session && session.isLoggedIn);
//     }

//     function getLoggedUser() {
//         const session = getSession();
//         return session && session.isLoggedIn ? session : null;
//     }

//     function findUserByEmail(email) {
//         const needle = normalizeEmail(email);
//         return getUsers().find(user => normalizeEmail(user.email) === needle) || null;
//     }

//     function findUserByUsername(username) {
//         const needle = String(username || "").trim().toLowerCase();
//         return getUsers().find(user => String(user.username || "").trim().toLowerCase() === needle) || null;
//     }

//     function upsertUser(updatedUser) {
//         const users = getUsers();
//         const index = users.findIndex(user => normalizeEmail(user.email) === normalizeEmail(updatedUser.email));

//         if (index >= 0) {
//             users[index] = { ...users[index], ...updatedUser };
//         } else {
//             users.push(updatedUser);
//         }

//         saveUsers(users);
//     }

//     function requireLogin(redirectUrl = "Homepage.html?auth=login") {
//         if (!isLoggedIn()) {
//             window.location.href = redirectUrl;
//             return false;
//         }
//         return true;
//     }

//     return {
//         normalizeEmail,
//         isValidEmail,
//         getUsers,
//         saveUsers,
//         getSession,
//         saveSession,
//         clearSession,
//         isLoggedIn,
//         getLoggedUser,
//         findUserByEmail,
//         findUserByUsername,
//         upsertUser,
//         requireLogin,
//         USERS_KEY,
//         SESSION_KEY
//     };
// })();