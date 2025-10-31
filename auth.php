<?php
session_start();
include 'db.php'; // MySQL connection

// --- Sign Up ---
if(isset($_POST['signup'])){
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if($stmt->num_rows > 0){
        echo json_encode(["status"=>"error","message"=>"Email already registered"]);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name,email,password) VALUES (?,?,?)");
    $stmt->bind_param("sss",$full_name,$email,$hashed_password);
    if($stmt->execute()){
        echo json_encode(["status"=>"success","message"=>"Sign Up Successful!"]);
    } else {
        echo json_encode(["status"=>"error","message"=>"Error occurred"]);
    }
    exit;
}

// --- Sign In ---
if(isset($_POST['signin'])){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $stmt->bind_result($id,$full_name,$hashed_password);
    if($stmt->fetch()){
        if(password_verify($password,$hashed_password)){
            $_SESSION['user_id'] = $id;
            $_SESSION['full_name'] = $full_name;
            echo json_encode(["status"=>"success","message"=>"Login Successful"]);
        } else {
            echo json_encode(["status"=>"error","message"=>"Incorrect Password"]);
        }
    } else {
        echo json_encode(["status"=>"error","message"=>"Email not registered"]);
    }
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CollExpress - Student Earn</title>
<style>
/* Global reset */
* { margin:0; padding:0; box-sizing:border-box; }

/* HTML */
html {
  height: 100%;
  width: 100%;
  overflow-y: auto;
  background: #0a192f;
  position: relative;
}

/* Body */
body {
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  padding: 0;
  background: radial-gradient(circle at 20% 20%, #0a192f, #1d3557 50%, #0a192f 100%);
  overflow: hidden;
  animation: bgShift 20s linear infinite;
  position: relative;
}

/* Animated background gradient shift */
@keyframes bgShift {
  0% { background-position: 0% 0%; }
  50% { background-position: 100% 100%; }
  100% { background-position: 0% 0%; }
}

/* Floating particles effect */
body::before {
  content: '';
  position: absolute;
  width: 200%;
  height: 200%;
  top: -50%;
  left: -50%;
  background: radial-gradient(circle, rgba(255,255,255,0.05) 1px, transparent 1px);
  background-size: 50px 50px;
  animation: floatParticles 60s linear infinite;
  pointer-events: none;
  z-index: 1;
}

@keyframes floatParticles {
  0% { transform: translate(0,0); }
  100% { transform: translate(50px, 50px); }
}

/* Auth box */
.auth-box {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 420px;
  padding: 2.5rem 2rem;
  color: #fff;
  border-radius: 20px;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.05);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
  animation: fadeIn 1.2s ease;
  border: 1px solid rgba(255,255,255,0.1);
}

/* Heading */
h2 {
  text-align: center;
  margin-bottom: 1.5rem;
  font-size: 2rem;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.6rem;
}
.welcome-emoji {
  font-size: 2.2rem;
  animation: wave 2s infinite;
  transform-origin: 70% 70%;
  display: inline-block;
}
@keyframes wave {
  0%{transform:rotate(0deg);} 20%{transform:rotate(20deg);} 40%{transform:rotate(-10deg);}
  60%{transform:rotate(20deg);} 80%{transform:rotate(-5deg);} 100%{transform:rotate(0deg);}
}

/* Tabs */
.tabs {
  display: flex;
  justify-content: center;
  margin-bottom: 2rem;
  border-radius: 12px;
  overflow: hidden;
  background: rgba(255,255,255,0.1);
}
.tab {
  flex: 1;
  padding: 0.8rem;
  text-align: center;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.3s ease, color 0.3s ease;
  color: #eee;
}
.tab.active {
  background: linear-gradient(135deg,#06d6a0,#118ab2);
  color:white;
}

/* Forms */
form {
  display: none;
  flex-direction: column;
}
form.active { display: flex; animation: slideUp 0.5s ease; }

/* Inputs */
input {
  width: 100%;
  padding: 0.9rem;
  margin: 0.6rem 0;
  border: 1px solid rgba(255,255,255,0.3);
  border-radius: 12px;
  background: rgba(255,255,255,0.1);
  color: #fff;
  transition: border 0.3s ease, box-shadow 0.3s ease;
}
input::placeholder { color: #ccc; }
input:focus {
  border: 1px solid #06d6a0;
  box-shadow: 0 0 10px rgba(6,214,160,0.6);
  outline: none;
}

/* Buttons */
button {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg,#06d6a0,#118ab2);
  color: white;
  border: none;
  border-radius: 15px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
button:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(6,214,160,0.6);
}

/* Alternative text */
.alt { text-align:center; margin-top:1rem; font-size:0.9rem; color:#ddd; }
.alt a { color:#06d6a0; text-decoration:none; font-weight:600; }

/* Error message */
.error { color:#ff6b6b; text-align:center; margin-bottom:1rem; }

/* Animations */
@keyframes slideUp { from{opacity:0; transform:translateY(15px);} to{opacity:1; transform:translateY(0);} }
@keyframes fadeIn { from{opacity:0; transform:scale(0.95);} to{opacity:1; transform:scale(1);} }

/* Responsive */
@media(max-width:480px){
  .auth-box { padding:1.5rem; margin:20px; }
  h2 { font-size:1.6rem; }
  .tab { font-size:0.9rem; }
  .welcome-emoji { font-size:1.8rem; }
}

/* Login Success / Error Card Styles */
.login-success-container, .login-error-container {
  position: fixed;
  top:0; left:0;
  width:100%;
  height:100%;
  display:flex;
  align-items:center;
  justify-content:center;
  background: rgba(0,0,0,0.5);
  z-index: 9999;
  animation: fadeIn 0.5s ease;
}

.login-success-card, .login-error-card {
  background: white;
  padding: 2rem 2.5rem;
  border-radius: 20px;
  max-width: 400px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  animation: slideUp 0.5s ease-out;
}

.login-success-card h1 { font-size: 1.8rem; margin-bottom: 0.5rem; color: #1e293b; }
.login-success-card p { color: #4b5563; margin-bottom: 1rem; }

.login-icon {
  font-size: 3rem;
  color: #10b981;
  margin-bottom: 1rem;
  animation: pulse 1.5s infinite;
}

.login-error-card p { color: #ef4444; font-weight: 600; }

.btn.btn-primary {
  background: linear-gradient(135deg,#667eea,#764ba2);
  color: white;
  padding: 0.75rem 2rem;
  border-radius: 12px;
  display: inline-block;
  text-decoration: none;
  font-weight: 600;
  margin-top: 1rem;
}

@keyframes pulse {
  0%,100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

</style>
</head>
<body>
<div class="overlay"></div>

<div class="auth-box">
  <h2><span class="welcome-emoji">👋</span> Welcome Student</h2>
  <div class="tabs">
    <div class="tab active" onclick="switchTab('signup')">Sign Up</div>
    <div class="tab" onclick="switchTab('signin')">Sign In</div>
  </div>
  <form id="signupForm" class="active">
    <input type="text" name="full_name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Sign Up</button>
  </form>
  <form id="signinForm">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Sign In</button>
  </form>
</div>

<script>
// Tab switch
function switchTab(tab) {
  const signup = document.getElementById('signupForm');
  const signin = document.getElementById('signinForm');
  const tabs = document.querySelectorAll('.tab');
  if (tab === 'signup') {
    signup.classList.add('active');
    signin.classList.remove('active');
    tabs[0].classList.add('active');
    tabs[1].classList.remove('active');
  } else {
    signin.classList.add('active');
    signup.classList.remove('active');
    tabs[1].classList.add('active');
    tabs[0].classList.remove('active');
  }
}

// AJAX Submit Sign Up
document.getElementById('signupForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('signup', '1');
  const res = await fetch('auth.php', {method:'POST', body: formData});
  const data = await res.json();
  alert(data.message);
  if(data.status === 'success') switchTab('signin');
});

// AJAX Submit Sign In
document.getElementById('signinForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const formData = new FormData(this);
  formData.append('signin', '1');
  const res = await fetch('auth.php', {method:'POST', body: formData});
  const data = await res.json();

  if(data.status === 'success'){
    showLoginSuccess(data.message, data.full_name || 'Student');
  } else {
    showLoginError(data.message);
  }
});

// Function to show attractive login success
function showLoginSuccess(message, name){
  const div = document.createElement('div');
  div.className = 'login-success-container';
  div.innerHTML = `
    <div class="login-success-card">
      <div class="login-icon"><i class="fa-solid fa-circle-check"></i></div>
      <h1>Welcome Back!</h1>
      <p>Hello, <strong>${name}</strong></p>
      <p>${message}</p>
      <div class="countdown">Redirecting in <span id="loginCountdown">5</span> seconds...</div>
      <a href="dashboard.php" class="btn btn-primary">Go Now</a>
    </div>
  `;
  document.body.appendChild(div);

  let countdown = 5;
  const counterEl = document.getElementById('loginCountdown');
  const timer = setInterval(()=>{
    countdown--;
    counterEl.textContent = countdown;
    if(countdown <= 0){
      clearInterval(timer);
      window.location.href = 'dashboard.php';
    }
  }, 1000);
}

// Function to show error
function showLoginError(message){
  const div = document.createElement('div');
  div.className = 'login-error-container';
  div.innerHTML = `<div class="login-error-card"><p>${message}</p></div>`;
  document.body.appendChild(div);
  setTimeout(()=> div.remove(), 4000);
}

</script>
</body>
</html>
