<?php 
require_once __DIR__ . '/../includes/config.php';
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | UrbanThrift</title>
    <link rel="stylesheet" href="/IMprojFinal/public/css/style.css">
    <style>
        .about-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Hero Section */
        .about-hero {
            text-align: center;
            padding: 5rem 2rem;
            background: linear-gradient(135deg, rgba(155, 77, 224, 0.1) 0%, transparent 100%);
            border-radius: var(--radius-xl);
            margin-bottom: 5rem;
        }

        .about-hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .about-hero p {
            font-size: clamp(1.1rem, 2vw, 1.4rem);
            color: var(--text-secondary);
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
        }

        /* Story Section */
        .story-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-bottom: 6rem;
            align-items: center;
        }

        .story-content h2 {
            font-size: 2.5rem;
            color: var(--primary-light);
            margin-bottom: 1.5rem;
        }

        .story-content p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .story-image {
            background: linear-gradient(135deg, var(--dark-light) 0%, var(--dark) 100%);
            padding: 3rem;
            border-radius: var(--radius-xl);
            border: 2px solid rgba(155, 77, 224, 0.2);
            text-align: center;
        }

        .story-image .icon {
            font-size: 8rem;
            margin-bottom: 1rem;
        }

        /* Mission & Vision */
        .values-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 6rem;
        }

        .value-card {
            background: var(--dark-light);
            padding: 3rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(155, 77, 224, 0.2);
            transition: var(--transition);
            text-align: center;
        }

        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-glow);
            border-color: var(--primary);
        }

        .value-card .icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }

        .value-card h3 {
            font-size: 1.8rem;
            color: var(--primary-light);
            margin-bottom: 1rem;
        }

        .value-card p {
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        /* Team Section */
        .team-section {
            text-align: center;
            margin-bottom: 4rem;
        }

        .team-section h2 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .team-section .subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 4rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .team-card {
            background: var(--dark-light);
            padding: 3rem 2rem;
            border-radius: var(--radius-xl);
            border: 2px solid rgba(155, 77, 224, 0.2);
            transition: var(--transition);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .team-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(155, 77, 224, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: var(--transition);
        }

        .team-card:hover {
            transform: translateY(-15px);
            box-shadow: var(--shadow-glow);
            border-color: var(--primary);
        }

        .team-card:hover::before {
            opacity: 1;
        }

        .avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 3rem;
            font-weight: 800;
            color: white;
            box-shadow: 0 10px 30px rgba(155, 77, 224, 0.4);
            position: relative;
            z-index: 1;
        }

        .team-card:hover .avatar {
            transform: scale(1.1) rotate(5deg);
        }

        .name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .role {
            font-size: 1rem;
            color: var(--primary-light);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, rgba(155, 77, 224, 0.05) 0%, transparent 100%);
            padding: 4rem 2rem;
            border-radius: var(--radius-xl);
            margin: 4rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            text-align: center;
        }

        .stat-item {
            position: relative;
        }

        .stat-number {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        /* CTA Section */
        .cta-section {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--dark-light) 0%, var(--dark) 100%);
            border-radius: var(--radius-xl);
            border: 2px solid rgba(155, 77, 224, 0.2);
        }

        .cta-section h2 {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .cta-button {
            display: inline-block;
            padding: 1.25rem 3rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-weight: 700;
            font-size: 1.1rem;
            transition: var(--transition);
            box-shadow: 0 10px 40px rgba(155, 77, 224, 0.4);
        }

        .cta-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(155, 77, 224, 0.6);
        }

        @media (max-width: 968px) {
            .story-section {
                grid-template-columns: 1fr;
            }

            .about-hero {
                padding: 3rem 1rem;
            }

            .team-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="about-container">
    <!-- Hero Section -->
    <div class="about-hero">
        <h1>About UrbanThrift</h1>
        <p>
            We're on a mission to revolutionize sustainable fashion by making thrift shopping 
            accessible, stylish, and impactful. Join us in creating a circular economy 
            where fashion meets responsibility.
        </p>
    </div>

    <!-- Story Section -->
    <div class="story-section">
        <div class="story-content">
            <h2>Our Story</h2>
            <p>
                UrbanThrift was born from a simple idea: fashion doesn't have to be wasteful. 
                We saw mountains of perfectly good clothing going to waste while people craved 
                affordable, unique styles.
            </p>
            <p>
                Today, we're proud to be your go-to sustainable fashion platform, helping small 
                businesses manage inventory, customers, expenses, and transactions with ease—all 
                while reducing fashion's environmental footprint.
            </p>
            <p>
                Every item in our collection tells a story, and we're here to help it continue.
            </p>
        </div>
        <div class="story-image">
            <div class="icon">♻️</div>
            <h3 style="color: var(--primary-light); font-size: 1.5rem;">Sustainability First</h3>
        </div>
    </div>

    <!-- Values Section -->
    <div class="values-section">
        <div class="value-card">
            <div class="icon">🎯</div>
            <h3>Our Mission</h3>
            <p>
                To make sustainable fashion the norm, not the exception. We empower businesses 
                and customers to make eco-conscious choices without compromising on style or affordability.
            </p>
        </div>

        <div class="value-card">
            <div class="icon">👁️</div>
            <h3>Our Vision</h3>
            <p>
                A world where every clothing item is valued, reused, and loved—creating a truly 
                circular fashion economy that benefits people and the planet.
            </p>
        </div>

        <div class="value-card">
            <div class="icon">💚</div>
            <h3>Our Values</h3>
            <p>
                Sustainability, transparency, community, and style. We believe in doing business 
                the right way—ethically, honestly, and with care for our planet's future.
            </p>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">5K+</div>
                <div class="stat-label">Items Saved</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">10K+</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50T</div>
                <div class="stat-label">Waste Reduced</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfaction Rate</div>
            </div>
        </div>
    </div>

    <!-- Team Section -->
    <div class="team-section">
        <h2>Meet the Team</h2>
        <p class="subtitle">The passionate people behind UrbanThrift</p>

        <div class="team-grid">
            <div class="team-card">
                <div class="avatar">S</div>
                <div class="name">Sedriel H. Navasca</div>
                <div class="role">Backend Developer</div>
            </div>

            <div class="team-card">
                <div class="avatar">A</div>
                <div class="name">Ardee Jhade B. Orlanda</div>
                <div class="role">Frontend Developer</div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
        <h2>Ready to Start Your Sustainable Fashion Journey?</h2>
        <p>Join thousands of conscious shoppers making a difference</p>
        <a href="shop.php" class="cta-button">Explore Our Collection →</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
