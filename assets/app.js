// assets/app.js

function $(id){ return document.getElementById(id); }

function isValidEmail(email){
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Password rules:
// - min 8 characters
// - at least 1 uppercase
// - at least 1 lowercase
// - at least 1 number
// - at least 1 special char
function isStrongPassword(p){
  if (!p || p.length < 8) return false;
  const hasUpper = /[A-Z]/.test(p);
  const hasLower = /[a-z]/.test(p);
  const hasNum   = /[0-9]/.test(p);
  const hasSpec  = /[^A-Za-z0-9]/.test(p);
  return hasUpper && hasLower && hasNum && hasSpec;
}

function registerValidate(){
  const name = $("full_name").value.trim();
  const email = $("email").value.trim();
  const phone = ($("phone")?.value || "").trim();
  const pass1 = $("password").value;
  const pass2 = $("confirm_password").value;

  if(name.length < 3){ alert("Full name must be at least 3 characters."); return false; }
  if(!isValidEmail(email)){ alert("Please enter a valid email address."); return false; }

  // phone optional, but if entered validate length
  if(phone && phone.replace(/\D/g,'').length < 9){
    alert("Please enter a valid phone number.");
    return false;
  }

  if(!isStrongPassword(pass1)){
    alert("Password must be 8+ chars and include uppercase, lowercase, number, and special character.");
    return false;
  }

  if(pass1 !== pass2){ alert("Passwords do not match."); return false; }
  return true;
}

function loginValidate(){
  const email = $("email").value.trim();
  const pass  = $("password").value;
  if(!isValidEmail(email)){ alert("Please enter a valid email address."); return false; }
  if(pass.length < 1){ alert("Please enter your password."); return false; }
  return true;
}

// Optional: toggle password visibility if you add an eye button
function togglePassword(id){
  const el = $(id);
  if(!el) return;
  el.type = (el.type === "password") ? "text" : "password";
}