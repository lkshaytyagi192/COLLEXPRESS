<?php
session_start();
include 'db.php';

if(isset($_POST['signin'])){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password FROM service_providers WHERE email=?");
    
    if ($stmt === false) {
        echo json_encode(["status"=>"error","message"=>"Database error: " . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $stmt->bind_result($id,$full_name,$hashed_password);

    if($stmt->fetch()){
        if(password_verify($password,$hashed_password)){
            $_SESSION['sp_id'] = $id;
            $_SESSION['sp_full_name'] = $full_name;
            echo json_encode(["status"=>"success","message"=>"Login Successful"]);
        } else {
            echo json_encode(["status"=>"error","message"=>"Incorrect Password"]);
        }
    } else {
        echo json_encode(["status"=>"error","message"=>"Email not registered"]);
    }
    $stmt->close();
    exit;
}

if(isset($_POST['signup'])){
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $check_stmt = $conn->prepare("SELECT id FROM service_providers WHERE email=?");
    if ($check_stmt === false) {
        echo json_encode(["status"=>"error","message"=>"Database error: " . $conn->error]);
        exit;
    }
    
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if($check_stmt->num_rows > 0){
        echo json_encode(["status"=>"error","message"=>"Email already registered"]);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insert_stmt = $conn->prepare("INSERT INTO service_providers (full_name, email, password) VALUES (?, ?, ?)");
    
    if ($insert_stmt === false) {
        echo json_encode(["status"=>"error","message"=>"Database error: " . $conn->error]);
        exit;
    }
    
    $insert_stmt->bind_param("sss", $full_name, $email, $hashed_password);
    
    if($insert_stmt->execute()){
        echo json_encode(["status"=>"success","message"=>"Registration successful! Please sign in."]);
    } else {
        echo json_encode(["status"=>"error","message"=>"Registration failed. Please try again."]);
    }
    $insert_stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CollExpress - Service Provider</title>
  <style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
body {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg,#0a192f,#1d3557,#a8dadc,#f1faee);
  background-size: 400% 400%;
  animation: gradientShift 18s ease infinite;
  color: #fff;
  margin: 0;
}
@keyframes gradientShift { 0% { background-position:0% 50%; } 50% { background-position:100% 50%; } 100% { background-position:0% 50%; } }
.overlay { position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.3); pointer-events:none; }
.auth-box {
  position: relative;
  max-width: 420px;
  width: 90%;
  margin: 2rem auto;
  background: rgba(255,255,255,0.1);
  border:1px solid rgba(255,255,255,0.25);
  border-radius:20px;
  padding:2rem;
  backdrop-filter: blur(20px);
  box-shadow: 0 20px 60px rgba(0,0,0,0.6);
  color:#f0f0f0;
}
h2 { text-align:center; margin-bottom:1.5rem; font-size:2rem; display:flex; justify-content:center; align-items:center; gap:0.6rem; }
.welcome-emoji { font-size:2.2rem; animation:wave 2s infinite; transform-origin:70% 70%; display:inline-block; }
@keyframes wave { 0% { transform:rotate(0deg); } 20% { transform:rotate(20deg); } 40% { transform:rotate(-10deg); } 60% { transform:rotate(20deg); } 80% { transform:rotate(-5deg); } 100% { transform:rotate(0deg); } }
.tabs { display:flex; justify-content:center; margin-bottom:2rem; border-radius:12px; overflow:hidden; background: rgba(255,255,255,0.2); }
.tab { flex:1; padding:0.8rem; text-align:center; cursor:pointer; font-weight:600; transition:background 0.3s ease; color:#eee; }
.tab.active { background: linear-gradient(135deg,#06d6a0,#118ab2); color:white; }
form { display:none; flex-direction:column; }
form.active { display:flex; animation: slideUp 0.5s ease; }
input { width:100%; padding:0.9rem; margin:0.6rem 0; border:1px solid rgba(255,255,255,0.3); border-radius:12px; background: rgba(255,255,255,0.15); color:#fff; transition:border 0.3s ease, box-shadow 0.3s ease; }
input::placeholder { color:#ccc; }
input:focus { border:1px solid #06d6a0; box-shadow:0 0 8px rgba(6,214,160,0.6); outline:none; }
button { width:100%; padding:1rem; background: linear-gradient(135deg,#06d6a0,#118ab2); color:white; border:none; border-radius:15px; font-weight:600; cursor:pointer; margin-top:1rem; transition: transform 0.3s ease, box-shadow 0.3s ease; }
button:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(6,214,160,0.6); }
.alt { text-align:center; margin-top:1rem; font-size:0.9rem; color:#ddd; }
.alt a { color:#06d6a0; text-decoration:none; font-weight:600; }
.error, .success { text-align:center; margin-bottom:1rem; padding:0.5rem; border-radius:8px; }
.error { color:#ff6b6b; background:rgba(255,107,107,0.1); border:1px solid rgba(255,107,107,0.3); }
.success { color:#06d6a0; background:rgba(6,214,160,0.1); border:1px solid rgba(6,214,160,0.3); }
@keyframes slideUp { from{opacity:0; transform:translateY(15px);} to{opacity:1; transform:translateY(0);} }
@media(max-width:480px) { .auth-box { padding:1.5rem; margin:20px; } h2 { font-size:1.6rem; } .tab { font-size:0.9rem; } .welcome-emoji { font-size:1.8rem; } }
</style>
</head>
<body>
  <div class="overlay"></div>

  <div class="auth-box">
    <h2><span class="welcome-emoji">🤝</span> Service Provider</h2>
    <div id="message"></div>
    <div class="tabs">
      <div class="tab active" onclick="switchTab('signup')">Sign Up</div>
      <div class="tab" onclick="switchTab('signin')">Sign In</div>
    </div>

    <form id="signupForm" class="active">
      <input type="text" name="full_name" placeholder="Full Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="signup">Sign Up</button>
    </form>

    <form id="signinForm">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="signin">Sign In</button>
    </form>

    <div class="alt">Need help? <a href="#" onclick="switchTab('signin')">Contact Support</a></div>
  </div>

  <script>
    function switchTab(tab) {
      const signup = document.getElementById('signupForm');
      const signin = document.getElementById('signinForm');
      const tabs = document.querySelectorAll('.tab');
      document.getElementById('message').innerHTML = '';
      
      if(tab === 'signup') {
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

    document.getElementById('signupForm').addEventListener('submit', function(e) {
      e.preventDefault();
      handleForm(this, 'signup');
    });

    document.getElementById('signinForm').addEventListener('submit', function(e) {
      e.preventDefault();
      handleForm(this, 'signin');
    });

    function handleForm(form, action) {
      const formData = new FormData(form);
      formData.append(action, '1');
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        const messageDiv = document.getElementById('message');
        if(data.status === 'success') {
          messageDiv.innerHTML = '<div class="success">' + data.message + '</div>';
          if(action === 'signin') {
            setTimeout(() => {
              window.location.href = 'sp-dashboard.php';
            }, 1500);
          } else {
            setTimeout(() => switchTab('signin'), 2000);
          }
        } else {
          messageDiv.innerHTML = '<div class="error">' + data.message + '</div>';
        }
      })
      .catch(error => {
        document.getElementById('message').innerHTML = '<div class="error">An error occurred. Please try again.</div>';
      });
    }
  </script>
</body>
</html>