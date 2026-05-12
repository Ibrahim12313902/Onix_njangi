<?php
require_once 'config.php';

if (isMemberLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONIX Njangi - Group Savings Made Simple</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            color: #fff;
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .bg-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .bg-shapes span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.05);
            animation: float 25s linear infinite;
            bottom: -150px;
            border-radius: 50%;
        }
        
        .bg-shapes span:nth-child(1) { left: 10%; width: 80px; height: 80px; animation-delay: 0s; }
        .bg-shapes span:nth-child(2) { left: 20%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
        .bg-shapes span:nth-child(3) { left: 25%; width: 20px; height: 20px; animation-delay: 4s; }
        .bg-shapes span:nth-child(4) { left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
        .bg-shapes span:nth-child(5) { left: 70%; width: 20px; height: 20px; animation-delay: 0s; }
        .bg-shapes span:nth-child(6) { left: 80%; width: 110px; height: 110px; animation-delay: 3s; }
        .bg-shapes span:nth-child(7) { left: 35%; width: 150px; height: 150px; animation-delay: 7s; }
        .bg-shapes span:nth-child(8) { left: 55%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s; }
        .bg-shapes span:nth-child(9) { left: 65%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s; }
        .bg-shapes span:nth-child(10) { left: 90%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s; }
        
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(-1000px) rotate(720deg); opacity: 0; }
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
            background: rgba(26, 26, 46, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 28px;
            font-weight: 700;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
        }
        
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #667eea;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }
        
        /* Hero Section */
        .hero {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 50px 80px;
        }
        
        .hero-content {
            max-width: 600px;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.3);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            margin-bottom: 25px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .hero h1 {
            font-size: 58px;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 25px;
        }
        
        .hero h1 span {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero p {
            font-size: 18px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 35px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 50px;
        }
        
        .hero-stats {
            display: flex;
            gap: 50px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* What is Njangi Section */
        .section {
            position: relative;
            z-index: 1;
            padding: 100px 50px;
        }
        
        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 60px;
        }
        
        .section-header h2 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .section-header p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 30px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(102, 126, 234, 0.5);
        }
        
        .card-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 25px;
        }
        
        .card h3 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .card p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
        }
        
        /* How It Works */
        .steps {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            max-width: 1100px;
            margin: 0 auto;
        }
        
        .step {
            flex: 1;
            min-width: 250px;
            max-width: 300px;
            text-align: center;
            position: relative;
        }
        
        .step-number {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            margin: 0 auto 25px;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }
        
        .step h3 {
            font-size: 20px;
            margin-bottom: 12px;
        }
        
        .step p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
        }
        
        /* Benefits */
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 25px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .benefit-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        
        .benefit-content h4 {
            font-size: 17px;
            margin-bottom: 8px;
        }
        
        .benefit-content p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
        }
        
        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 30px;
            padding: 80px 50px;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        .cta::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
        }
        
        .cta h2 {
            font-size: 38px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 18px;
            margin-bottom: 35px;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .btn-white {
            background: #fff;
            color: #667eea;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .btn-white-outline {
            background: transparent;
            border: 2px solid #fff;
            color: #fff;
        }
        
        .btn-white-outline:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Footer */
        .footer {
            position: relative;
            z-index: 1;
            padding: 50px;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }
        
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
            }
            
            .logo {
                font-size: 22px;
            }
            
            .logo-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            
            .hero {
                padding: 100px 20px 60px;
            }
            
            .hero h1 {
                font-size: 36px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .hero-buttons {
                flex-direction: column;
            }
            
            .hero-stats {
                flex-wrap: wrap;
                gap: 30px;
            }
            
            .section {
                padding: 60px 20px;
            }
            
            .section-header h2 {
                font-size: 30px;
            }
            
            .cta {
                padding: 50px 25px;
                border-radius: 20px;
            }
            
            .cta h2 {
                font-size: 28px;
            }
            
            .cta-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
    
    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-hand-holding-heart"></i>
            </div>
            <span>ONIX Njangi</span>
        </div>
        <div class="nav-buttons">
            <a href="login.php" class="btn btn-outline">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="register.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Join Now
            </a>
            <a href="set_password.php" class="btn btn-outline" style="background: #f59e0b; color: white;">
                <i class="fas fa-key"></i> Set Password
            </a>
            <a href="../login.php" class="btn btn-outline" style="background: #28a745; color: white;">
                <i class="fas fa-user-shield"></i> Admin Login
            </a>
        </div>
    </nav>
    
    <section class="hero">
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-star"></i> Trusted by 500+ Members
            </div>
            <h1>Group Savings<br><span>Made Simple</span></h1>
            <p>
                Join our community-driven savings group where members contribute regularly 
                and take turns receiving payouts. Build wealth together, achieve financial 
                goals faster, and grow your network.
            </p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">
                    <i class="fas fa-rocket"></i> Get Started Free
                </a>
                <a href="#how-it-works" class="btn btn-outline" style="padding: 15px 40px; font-size: 16px;">
                    <i class="fas fa-play-circle"></i> Learn More
                </a>
                <a href="../login.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 16px; background: #28a745; border-color: #28a745;">
                    <i class="fas fa-user-shield"></i> Admin Portal
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-value">500+</div>
                    <div class="stat-label">Active Members</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">10M+</div>
                    <div class="stat-label">FCFA Saved</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">50+</div>
                    <div class="stat-label">Cycles Completed</div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="section" id="what-is-njangi">
        <div class="section-header">
            <h2>What is a <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Njangi</span>?</h2>
            <p>
                A "Njangi" is a traditional African savings group where a group of people come together 
                to save money collectively. Each member contributes a fixed amount regularly, and in return, 
                each member gets a chance to receive the total pool of money - either through bidding 
                or a predetermined rotation.
            </p>
        </div>
        
        <div class="cards-grid">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Community Driven</h3>
                <p>
                    Njangi brings people together. You build lasting relationships while achieving 
                    your individual financial goals through collective strength.
                </p>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <h3>Save Consistently</h3>
                <p>
                    Regular contributions ensure you build a savings habit. Even small amounts 
                    add up to significant wealth over time.
                </p>
            </div>
            
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h3>Fair Distribution</h3>
                <p>
                    Every member gets their turn to receive the pooled funds. No one is left behind 
                    - it's a fair and transparent system.
                </p>
            </div>
        </div>
    </section>
    
    <section class="section" id="how-it-works" style="background: rgba(0,0,0,0.2);">
        <div class="section-header">
            <h2>How It <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Works</span></h2>
            <p>
                Getting started with ONIX Njangi is simple. Follow these easy steps to begin 
                your journey to financial freedom.
            </p>
        </div>
        
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Register</h3>
                <p>
                    Sign up with your basic information. It takes less than 2 minutes to create 
                    your account and become a member.
                </p>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <h3>Join a Cycle</h3>
                <p>
                    Become part of a savings cycle. You'll see your position in the payout 
                    order and know exactly when you'll receive your funds.
                </p>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <h3>Contribute</h3>
                <p>
                    Make regular payments according to your hand type. Upload proof of payment 
                    and track your contributions easily.
                </p>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <h3>Receive Payout</h3>
                <p>
                    When it's your turn, receive your payout! See the full transparency of 
                    who's paid, who hasn't, and cycle progress.
                </p>
            </div>
        </div>
    </section>
    
    <section class="section">
        <div class="section-header">
            <h2>Why Choose <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">ONIX Njangi</span>?</h2>
            <p>
                We've modernized the traditional Njangi system with technology to make it 
                more transparent, efficient, and accessible.
            </p>
        </div>
        
        <div class="benefits-grid">
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="benefit-content">
                    <h4>100% Transparency</h4>
                    <p>See exactly who's paid, who hasn't, and how much has been collected in real-time.</p>
                </div>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="benefit-content">
                    <h4>Clear Payout Schedule</h4>
                    <p>Know your exact position in line and when you'll receive your payout.</p>
                </div>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="benefit-content">
                    <h4>Access Anywhere</h4>
                    <p>Manage your savings from your phone. Check status, upload payments, and stay informed.</p>
                </div>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="benefit-content">
                    <h4>Smart Notifications</h4>
                    <p>Get reminders for upcoming payments and know when it's your turn to receive.</p>
                </div>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="benefit-content">
                    <h4>Secure & Safe</h4>
                    <p>Your data is protected. Admin approval required for all transactions.</p>
                </div>
            </div>
            
            <div class="benefit-item">
                <div class="benefit-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="benefit-content">
                    <h4>Group Communication</h4>
                    <p>Stay connected with other members through our group chat and announcements.</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="section">
        <div class="cta">
            <div class="cta-content">
                <h2>Ready to Start Saving?</h2>
                <p>
                    Join hundreds of members who are already building wealth together. 
                    Your financial journey starts with a single step.
                </p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-white">
                        <i class="fas fa-user-plus"></i> Create Free Account
                    </a>
                    <a href="login.php" class="btn btn-white-outline">
                        <i class="fas fa-sign-in-alt"></i> Login to Your Account
                    </a>
                    <a href="../login.php" class="btn btn-white-outline" style="border-color: #28a745; color: #28a745;">
                        <i class="fas fa-user-shield"></i> Admin Login
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <footer class="footer">
        <p>&copy; 2024 ONIX Njangi. Built with <i class="fas fa-heart" style="color: #667eea;"></i> for communities.</p>
        <p style="margin-top: 10px;">
            <a href="../login.php" style="color: #667eea; text-decoration: none;">
                <i class="fas fa-user-shield"></i> Admin Portal
            </a>
        </p>
    </footer>
    
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>