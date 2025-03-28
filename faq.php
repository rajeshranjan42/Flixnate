<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Flixnate</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dark-theme">
     <!-- Navigation -->
     <nav class="main-nav">
        <div class="nav-brand">
            <h1>Flixnate</h1>
        </div>
        <button class="mobile-menu-toggle" aria-label="Toggle menu">
            <span class="toggle-bar"></span>
            <span class="toggle-bar"></span>
            <span class="toggle-bar"></span>
        </button>
        <div class="nav-menu">
            <ul class="nav-list">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li class="has-dropdown">
                    <a href="movies.php"><i class="fas fa-film"></i> Movies</a>
                    <ul class="dropdown">
                        <li><a href="movies.php?genre=action">Action</a></li>
                        <li><a href="movies.php?genre=comedy">Comedy</a></li>
                        <li><a href="movies.php?genre=drama">Drama</a></li>
                        <li><a href="movies.php?genre=horror">Horror</a></li>
                        <li><a href="movies.php">View All</a></li>
                    </ul>
                </li>
                <li class="has-dropdown">
                    <a href="tv-shows.php"><i class="fas fa-tv"></i> TV Shows</a>
                    <ul class="dropdown">
                        <li><a href="tv-shows.php?genre=drama">Drama Series</a></li>
                        <li><a href="tv-shows.php?genre=comedy">Comedy Series</a></li>
                        <li><a href="tv-shows.php?genre=documentary">Documentary</a></li>
                        <li><a href="tv-shows.php">View All</a></li>
                    </ul>
                </li>
                <li><a href="categories.php"><i class="fas fa-list"></i> Categories</a></li>
                <li><a href="search.php"><i class="fas fa-search"></i> Search</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="has-dropdown user-menu">
                    <a href="#"><i class="fas fa-user"></i> Account</a>
                    <ul class="dropdown">
                        <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                        <li><a href="watchlist.php"><i class="fas fa-bookmark"></i> Watchlist</a></li>
                        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="menu-overlay"></div>

    <style>
    /* Navigation Styles */
    .main-nav {
        background: var(--dark-bg);
        padding: 0.8rem 1rem;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .nav-brand h1 {
        margin: 0;
        font-size: 1.8rem;
        background: linear-gradient(90deg, #8B5CF6, #EC4899);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        width: 30px;
        height: 24px;
        position: relative;
        z-index: 1001;
    }

    .toggle-bar {
        display: block;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, #8B5CF6, #EC4899);
        position: absolute;
        left: 0;
        transition: all 0.3s ease;
        border-radius: 2px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .toggle-bar:nth-child(1) { top: 0; }
    .toggle-bar:nth-child(2) { 
        top: 50%; 
        transform: translateY(-50%);
        background: linear-gradient(90deg, #9333EA, #DB2777);
    }
    .toggle-bar:nth-child(3) { 
        bottom: 0;
        background: linear-gradient(90deg, #7C3AED, #BE185D);
    }

    .mobile-menu-toggle:hover .toggle-bar {
        background: linear-gradient(90deg, #A855F7, #F472B6);
    }

    .mobile-menu-toggle.active .toggle-bar {
        background: #8B5CF6;
    }

    .mobile-menu-toggle.active .toggle-bar:nth-child(1) {
        transform: translateY(11px) rotate(45deg);
    }

    .mobile-menu-toggle.active .toggle-bar:nth-child(2) {
        opacity: 0;
        transform: translateX(-20px);
    }

    .mobile-menu-toggle.active .toggle-bar:nth-child(3) {
        transform: translateY(-11px) rotate(-45deg);
    }

    .nav-list {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .nav-list li {
        position: relative;
    }

    .nav-list a {
        color: var(--muted-text);
        text-decoration: none;
        font-size: 1rem;
        padding: 0.6rem 1rem;
        border-radius: 6px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .nav-list a i {
        font-size: 1.1rem;
    }

    .nav-list a:hover,
    .nav-list a.active {
        color: var(--text-color);
        background: rgba(255, 255, 255, 0.05);
    }

    /* Dropdown Styles */
    .has-dropdown {
        position: relative;
    }

    .dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        background: var(--darker-bg);
        min-width: 200px;
        padding: 0.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s ease;
    }

    .has-dropdown:hover .dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown li {
        margin: 0;
    }

    .dropdown a {
        padding: 0.8rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .user-menu .dropdown {
        right: 0;
        left: auto;
    }

    /* Responsive Navigation */
    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: block;
        }

        .nav-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 100%;
            height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 80px 20px 20px;
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        .nav-menu.active {
            right: 0;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
        }

        .nav-list {
            flex-direction: column;
            gap: 0.5rem;
            align-items: stretch;
        }

        .nav-list li {
            width: 100%;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-list a {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: #a9b1d6;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .nav-list a:hover,
        .nav-list a.active {
            color: #fff;
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.3);
            transform: translateX(5px);
        }

        .nav-list a i {
            color: #8B5CF6;
        }

        .dropdown {
            position: static;
            background: rgba(0, 0, 0, 0.2);
            min-width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem;
            border-radius: 8px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .has-dropdown.active .dropdown {
            display: block;
            animation: slideDown 0.3s ease forwards;
        }

        .dropdown a {
            padding: 0.8rem 1.5rem 0.8rem 3rem;
            margin: 0.3rem 0;
            background: rgba(255, 255, 255, 0.02);
        }

        .dropdown a:hover {
            background: rgba(139, 92, 246, 0.1);
        }

        .menu-overlay {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
        }

        .menu-overlay.active {
            display: block;
            opacity: 1;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        const overlay = document.querySelector('.menu-overlay');
        const dropdownItems = document.querySelectorAll('.has-dropdown');
        
        function toggleMenu() {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        menuToggle.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);

        // Handle dropdowns on mobile
        dropdownItems.forEach(item => {
            const link = item.querySelector('a');
            link.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    item.classList.toggle('active');
                }
            });
        });

        // Close menu on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && navMenu.classList.contains('active')) {
                toggleMenu();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && navMenu.classList.contains('active')) {
                toggleMenu();
            }
        });
    });
    </script>


    <div class="faq-container">
        <div class="faq-header">
            <h1>Frequently Asked Questions</h1>
            <p class="subtitle">Find answers to common questions about Flixnate</p>
        </div>

        <div class="faq-content">
            <div class="faq-section">
                <h2>General Questions</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What is Flixnate?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Flixnate is a premium streaming service that offers a wide variety of award-winning movies, TV shows, documentaries, and more on thousands of internet-connected devices.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How much does Flixnate cost?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Watch Flixnate on your smartphone, tablet, Smart TV, laptop, or streaming device, all for one fixed monthly fee. Plans range from $8.99 to $15.99 a month.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>Where can I watch?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Watch anywhere, anytime. Sign in with your Flixnate account to watch instantly on the web at flixnate.com from your personal computer or on any internet-connected device that offers the Flixnate app.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="faq-section">
                <h2>Account & Billing</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How do I cancel my subscription?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Flixnate is flexible. There are no annoying contracts and no commitments. You can easily cancel your account online in two clicks. There are no cancellation fees – start or stop your account anytime.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What payment methods do you accept?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We accept all major credit cards, PayPal, and various local payment methods depending on your country.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>Can I share my account?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>The Standard and Premium plans let you create separate profiles for different members of your household. Each profile gets personalized recommendations based on their watching habits.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="faq-section">
                <h2>Technical Questions</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What are the system requirements?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>To stream Flixnate, you'll need a stable internet connection with a minimum speed of 5 Mbps for HD quality and 15 Mbps for 4K Ultra HD quality. We support all modern web browsers and devices.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>Can I download content to watch offline?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes, with our mobile app you can download your favorite shows and movies to watch offline. This feature is available on all plans.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What video quality do you offer?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>We offer streaming in SD (480p), HD (720p and 1080p), and 4K Ultra HD (2160p) quality, depending on your subscription plan and internet connection speed.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-support">
                <h2>Still have questions?</h2>
                <p>Can't find the answer you're looking for? Please visit our <a href="contact.php">contact page</a> for more help.</p>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-wrapper">
            <div class="footer-section">
                <h3 class="footer-title">About Flixnate</h3>
                <p>Your premium streaming experience with unique content and features.</p>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="faq.php">FAQ</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 class="footer-title">Connect With Us</h3>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> Flixnate. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html> 