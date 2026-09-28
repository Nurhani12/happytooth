<?php
session_start();
include __DIR__ . '/../auth/connection.php'; // Establish connection first

if (!isset($_SESSION['Member_IC'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Happy Tooth Dental Clinic - Our Team</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        hr {
            border: none;
            height: 3px;
            width: 250px;
            background-color: #00c4cc;
            margin: 8px auto 30px auto;
            border-radius: 4px;
        }

        body {
            margin: 0;
            background-color: #e0f7fa;
            color: #333;
            overflow-x: hidden;
        }

        h1 {
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            color: white;
        }

        h2 {
            color: #00c4cc;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        p {
            line-height: 1.6;
        }

        .page-header {
            background-color: #00c4cc;
            padding: 40px 0;
            text-align: center;
        }

        .page-header h1,
        .page-header p {
            opacity: 0;
            transform: translateY(-20px);
            animation: slideInDown 0.8s ease-out forwards;
        }

        .page-header p {
            color: #f0f0f0;
            max-width: 700px;
            margin: 0 auto;
            animation-delay: 0.2s;
        }

        .staff-section {
            padding: 60px 0;
            background-color: #e0f7fa;

        }

        .staff-member-item {
            display: flex;
            align-items: center;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            padding: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            opacity: 0;
            transform: translateX(-50px);
            animation: slideInFromLeft 0.8s ease-out forwards;
        }

        .staff-member-item:nth-child(even) {
            transform: translateX(50px);
            animation: slideInFromRight 0.8s ease-out forwards;
        }

        .staff-member-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .staff-image-container {
            flex-shrink: 0;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #00c4cc;
            margin-right: 30px;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }

        .staff-member-item:hover .staff-image-container {
            transform: scale(1.05);
            border-color: #009ea6;
        }

        .staff-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
        }

        .staff-details-container {
            flex-grow: 1;
        }

        .staff-details-container h3 {
            font-size: 1.4rem;
            color: #00c4cc;
            font-weight: 600;
            margin-bottom: 0.25rem;
            transition: color 0.3s ease;
        }

        .staff-member-item:hover .staff-details-container h3 {
            color: #009ea6;
        }

        .staff-details-container h4 {
            font-size: 1.1rem;
            color: black;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .staff-details-container dl {
            margin: 0;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 5px 15px;
        }

        .staff-details-container dt {
            font-weight: 500;
            color: #008f94;
        }

        .staff-details-container dd {
            margin-bottom: 0;
            color: #555;
            text-align: left;
        }

        .staff-details-container .email-link {
            color: #00c4cc;
            text-decoration: none;
            transition: color 0.3s ease, transform 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .staff-details-container .email-link:hover {
            color: #009ea6;
            text-decoration: underline;
            transform: translateX(3px);
        }

        .staff-details-container .email-link i {
            margin-right: 8px;
            vertical-align: middle;
            font-size: 1.1em;
        }

        .quote-section {
            background-color: white;
            padding: 60px 0;
            text-align: center;
            font-style: italic;
            color: #0056b3;
            font-size: 1.5rem;
            box-shadow: inset 0 0 15px rgba(0, 196, 204, 0.1);
        }

        .quote-section p {
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
            font-weight: 500;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInScaleUp 1s ease-out forwards;
            animation-delay: 0.3s;
        }

        .call-to-action-section {
            background-color: #00c4cc;
            color: white;
            padding: 60px 0;
            text-align: center;
        }

        .call-to-action-section h2 {
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(-20px);
            animation: slideInDown 0.8s ease-out forwards;
            animation-delay: 0.5s;
        }

        .call-to-action-section .btn-light {
            background-color: white;
            color: #00c4cc;
            border: 2px solid white;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
            animation: slideInUp 0.8s ease-out forwards;
            animation-delay: 0.7s;
        }

        .call-to-action-section .btn-light:hover {
            background-color: #e0f7fa;
            color: #009ea6;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInScaleUp {
            from {
                opacity: 0;
                transform: scale(0.9);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        @media (max-width: 768px) {
            .staff-member-item {
                flex-direction: column;
                text-align: center;
                animation: none;
                transform: none;
                opacity: 1;
            }

            .staff-image-container {
                margin-right: 0;
                margin-bottom: 20px;
            }

            .staff-details-container dl {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .staff-details-container dt,
            .staff-details-container dd {
                text-align: center;
            }

            .quote-section p::before,
            .quote-section p::after {
                font-size: 2.5em;
                left: 5px;
                right: 5px;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <div class="container">
            <h1>Our Dedicated Team</h1>
            <p>Meet the skilled and compassionate professionals who are committed to providing you with exceptional
                dental care.</p>
        </div>
    </header>

    <section class="staff-section">
        <div class="container">
            <h2 class="text-center mb-2">Our Dentists</h2>
            <hr>

            <div class="staff-member-item">
                <div class="staff-image-container">
                    <img src="../img/ameen.png" alt="Dr. Ainul Ameen">
                </div>
                <div class="staff-details-container">
                    <h3>Dr. Ainul Ameen</h3>
                    <h4>Chief Dentist</h4>
                    <dl>
                        <dt>Full Name:</dt>
                        <dd>Dr. Ainul Ameen Bin Abdullah</dd>

                        <dt>Email:</dt>
                        <dd><a href="mailto:ainul.ameen@happytooth.com" class="email-link"><i
                                    class="bi bi-envelope-fill"></i>ainul.ameen@happytooth.com</a></dd>

                        <dt>Description:</dt>
                        <dd>As our lead dentist, Dr. Ainul Ameen brings extensive experience and a passion for
                            comprehensive dental health.</dd>
                    </dl>
                </div>
            </div>

            <div class="staff-member-item">
                <div class="staff-image-container">
                    <img src="../img/nabilah.jpg" alt="Dr. Nabilah">
                </div>
                <div class="staff-details-container">
                    <h3>Dr. Nabilah</h3>
                    <h4>Orthodontist</h4>
                    <dl>
                        <dt>Full Name:</dt>
                        <dd>Dr. Nabilah Binti Omar</dd>

                        <dt>Email:</dt>
                        <dd><a href="mailto:nabilah.ortho@happytooth.com" class="email-link"><i
                                    class="bi bi-envelope-fill"></i>nabilah.ortho@happytooth.com</a></dd>

                        <dt>Description:</dt>
                        <dd>Dr. Nabilah specializes in aligning teeth and jaws, creating beautiful, confident smiles
                            through expert orthodontic care.</dd>
                    </dl>
                </div>
            </div>

            <div class="staff-member-item">
                <div class="staff-image-container">
                    <img src="../img/ain.png" alt="Dr. Nur Ain">
                </div>
                <div class="staff-details-container">
                    <h3>Dr. Nur Ain</h3>
                    <h4>Pediatric Dentist</h4>
                    <dl>
                        <dt>Full Name:</dt>
                        <dd>Dr. Nur Ain Binti Razali</dd>

                        <dt>Email:</dt>
                        <dd><a href="mailto:nur.ain@happytooth.com" class="email-link"><i
                                    class="bi bi-envelope-fill"></i>nur.ain@happytooth.com</a></dd>

                        <dt>Description:</dt>
                        <dd>Dr. Nur Ain creates a fun and comfortable experience for our youngest patients, fostering
                            positive dental habits and healthy smiles.</dd>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <div class="quote-section">
        <div class="container">
            <p>"At Happy Tooth Dental Clinic, we believe every smile tells a story. Our team is dedicated to
                ensure yours is healthy, happy, and confident."</p>
        </div>
    </div>

    <section class="staff-section">
        <div class="container">
            <h2 class="text-center mb-2">Our Support Staff</h2>
            <hr>
            <div class="staff-member-item">
                <div class="staff-image-container">
                    <img src="../img/adlina.png" alt="Nurse Nur Adlina">
                </div>
                <div class="staff-details-container">
                    <h3>Nurse Nur Adlina</h3>
                    <h4>Dental Nurse</h4>
                    <dl>
                        <dt>Full Name:</dt>
                        <dd>Nur Adlina Binti Azmi</dd>

                        <dt>Email:</dt>
                        <dd><a href="mailto:adlina.nurse@happytooth.com" class="email-link"><i
                                    class="bi bi-envelope-fill"></i>adlina.nurse@happytooth.com</a></dd>

                        <dt>Description:</dt>
                        <dd>Nur Adlina ensures a welcoming environment and assists our dentists with care and
                            efficiency, making every visit comfortable.</dd>
                    </dl>
                </div>
            </div>

            <div class="staff-member-item">
                <div class="staff-image-container">
                    <img src="../img/nurhani.png" alt="Receptionist Hani">
                </div>
                <div class="staff-details-container">
                    <h3>Receptionist Hani</h3>
                    <h4>Front Desk</h4>
                    <dl>
                        <dt>Full Name:</dt>
                        <dd>Siti Nur Hani Binti Mansor</dd>

                        <dt>Email:</dt>
                        <dd><a href="mailto:hani.frontdesk@happytooth.com" class="email-link"><i
                                    class="bi bi-envelope-fill"></i>hani.frontdesk@happytooth.com</a></dd>

                        <dt>Description:</dt>
                        <dd>Hani is the friendly face of our clinic, ready to assist with appointments and ensuring a
                            smooth, pleasant experience for all patients.</dd>
                    </dl>
                </div>
            </div>
        </div>
    </section>

    <section class="call-to-action-section">
        <div class="container">
            <h2>Ready to Meet Your New Dental Family?</h2>
            <p class="lead text-white mb-4">Schedule your visit today and experience the Happy Tooth difference.</p>
            <a href="view_appt.php" class="btn btn-light">Book an Appointment</a>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>