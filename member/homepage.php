<?php
session_start();
include __DIR__ . '/../auth/connection.php';
if (!isset($_SESSION['Member_IC'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Happy Tooth Dental Clinic</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <style>
        * {
            font-family: 'Poppins', sans-serif;
            font-weight: 400;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            overflow-x: hidden;
        }

        hr {
            border: none;
            height: 3px;
            width: 250px;
            background-color: #00c4cc;
            margin: 8px auto 30px auto;
            border-radius: 4px;
        }

        h2 {
            color: #00c4cc;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        p {
            color: #333;
            line-height: 1.6;
        }

        .bg-container {
            position: relative;
            height: 100vh;
            background-image: url('../img/bg2.png');
            background-size: cover;
            background-position: top;
            background-repeat: no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            overflow: hidden;
        }

        .bg-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
            padding: 50px 100px;
            color: white;
        }

        .text-box {
            max-width: 50%;
            opacity: 0;
            transform: translateX(-50px);
            animation: slideInFromLeft 1s ease-out forwards;
        }

        .text-box h1 {
            font-weight: 600;
            font-size: 42px;
            margin-bottom: 6px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .text-box p {
            font-size: 18px;
            line-height: 1.5;
            text-align: justify;
            color: white;
        }

        .hero-image img {
            max-height: 500px;
            width: auto;
            opacity: 0;
            transform: translateX(50px);
            animation: slideInFromRight 1s ease-out forwards;
            animation-delay: 0.2s;
        }

        .hero-content .btn {
            transition: all 0.3s ease;
        }

        .hero-content .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .hero-content .btn-info {
            background-color: #00c4cc;
            border-color: #00c4cc;
        }

        .hero-content .btn-info:hover {
            background-color: #009ea6;
            border-color: #009ea6;
        }

        .hero-content .btn-light:hover {
            background-color: #e2e6ea;
            color: #00c4cc;
        }

        #about {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
            background-color: white;
        }

        #about .row img {
            border: 2px solid #00c4cc;
            padding: 4px;
            border-radius: 50%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 60px;
            height: 60px;
        }

        #about .row img:hover {
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(0, 196, 204, 0.5);
        }

        #about .col-md-6 {
            text-align: justify;
        }

        #about h6 {
            color: #00c4cc;
        }

        #about p {
            font-size: 14px;
        }

        #staff {
            background-color: #e0f7fa;
        }

        #staff .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        #staff .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
        }

        #staff .card-img-top {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        #staff .card:hover .card-img-top {
            transform: scale(1.05);
        }

        #staff .card-title {
            font-size: 18px;
            color: #00c4cc;
            font-weight: 600;
        }

        #staff .card-text {
            font-size: 14px;
            color: #555;
        }

        .btn-info {
            background-color: #00c4cc;
            border: none;
            padding: 10px 24px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 6px;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .btn-info:hover {
            background-color: #009ea6;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 196, 204, 0.3);
        }

        .filter-btn {
            background-color: white !important;
            color: #00c4cc !important;
            border-color: #00c4cc !important;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background-color: #00c4cc !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 196, 204, 0.2);
        }

        .filter-btn.active {
            background-color: #00c4cc !important;
            color: white !important;
            box-shadow: 0 2px 4px rgba(0, 196, 204, 0.3);
        }

        #policy {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
        }

        #policy p {
            max-width: 700px;
            font-size: 16px;
        }

        #contact {
            background-color: #e0f7fa;
        }

        #contact .icon-circle {
            width: 50px;
            height: 50px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00c4cc;
            font-size: 20px;
            border: 2px solid #00c4cc;
            transition: all 0.3s ease;
        }

        #contact .icon-circle:hover {
            background-color: #00c4cc;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(0, 196, 204, 0.5);
        }

        #contact .info-text {
            font-size: 16px;
        }

        .map-responsive {
            overflow: hidden;
            padding-bottom: 56.25%;
            position: relative;
            height: 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .map-responsive:hover {
            transform: scale(1.01);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .map-responsive iframe {
            left: 0;
            top: 0;
            height: 100%;
            width: 100%;
            position: absolute;
            border-radius: 10px;
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <section id="home">
        <div class="bg-container">
            <div class="hero-content">
                <div class="text-box">
                    <h1>Your Smile, Our Passion</h1>
                    <p>Start your journey to a healthier, brighter smile with us. We’re a warm, caring team dedicated to
                        your oral health—whether you’re visiting for a routine check-up, expert advice, or a complete
                        smile makeover. Your comfort and confidence are our top priorities.</p>
                    <div class="mt-4 d-flex gap-3">
                        <a href="./view_package.php" class="btn btn-info text-white px-4 py-2">Upgrade Membership</a>
                        <a href="./view_appt.php" class="btn btn-light px-4 py-2">Book Appointment</a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="../img/new tooth.png" alt="Tooth Image" />
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-5">
        <div class="container">
            <div class="text-center mb-8">
                <h2>About Us</h2>
                <hr>
            </div>

            <div class="row text-start">
                <div class="col-md-6 mb-4">
                    <h4>Our Story</h4>
                    <p>Founded in 2010, Happy Tooth Dental Clinic has been providing exceptional dental care to our
                        community for over 5 years. Our mission is to create a comfortable environment where patients
                        can receive the highest quality dental treatment.<br><br>We believe in preventative care and
                        education as the keys to optimal dental health. We strive to provide dental health care rather
                        than disease care, which is why we focus on thorough exams and cleaning techniques for our
                        patients.</p>
                </div>
                <div class="col-md-6 mb-4">
                    <h4>Why Choose Us?</h4>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <img src="../img/care.png" alt="Gentle Care">
                            <div>
                                <h6 class="mb-1">Gentle Care</h6>
                                <p class="mb-0">We make sure every patient feels calm and safe during treatment.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <img src="../img/expert.png" alt="Expert Team">
                            <div>
                                <h6 class="mb-1">Expert Team</h6>
                                <p class="mb-0">Friendly and skilled dentists with years of experience.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <img src="../img/tools.png" alt="Modern Tools">
                            <div>
                                <h6 class="mb-1">Modern Tools</h6>
                                <p class="mb-0">We use the latest equipment for fast and precise treatment.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="staff" class="py-5">
        <div class="container text-center">
            <h2 class="mb-3">Meet Our Team</h2>
            <hr>
            <p>
                Our experienced team of dental professionals is dedicated to providing you with the highest quality care
                in a comfortable and friendly environment.
            </p>
            <div class="d-flex justify-content-center gap-3 mb-4">
                <button class="btn btn-outline-info px-4 filter-btn active" data-filter="all">All Team</button>
                <button class="btn btn-outline-info px-4 filter-btn" data-filter="specialist">Specialist</button>
                <button class="btn btn-outline-info px-4 filter-btn" data-filter="support">Support Staff</button>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-4 col-lg-2 mb-4 staff-card" data-role="support">
                    <div class="card h-100 shadow-sm">
                        <img src="../img/adlina.png" class="card-img-top" alt="Staff 1">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Nurse Nur Adlina</h5>
                            <p class="card-text text-muted">Dental Nurse</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 mb-4 staff-card" data-role="specialist">
                    <div class="card h-100 shadow-sm">
                        <img src="../img/nabilah.jpg" class="card-img-top" alt="Staff 2">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Dr. Nabilah</h5>
                            <p class="card-text text-muted">Orthodontist</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 mb-4 staff-card" data-role="specialist">
                    <div class="card h-100 shadow-sm">
                        <img src="../img/ameen.png" class="card-img-top" alt="Staff 3">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Dr. Ainul Ameen</h5>
                            <p class="card-text text-muted">Chief Dentist</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 mb-4 staff-card" data-role="specialist">
                    <div class="card h-100 shadow-sm">
                        <img src="../img/ain.png" class="card-img-top" alt="Staff 4">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Dr. Nur Ain</h5>
                            <p class="card-text text-muted">Pediatric Dentist</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2 mb-4 staff-card" data-role="support">
                    <div class="card h-100 shadow-sm">
                        <img src="../img/nurhani.png" class="card-img-top" alt="Staff 5">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Receptionist Hani</h5>
                            <p class="card-text text-muted">Front Desk</p>
                        </div>
                    </div>
                </div>
            </div>
            <a href="./staff_dir.php" class="btn btn-info text-white mt-3">Staff Directory</a>
        </div>
    </section>
    <section id="policy" class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="mb-3">Web Policy Overview</h2>
                <hr>
                <p class="mx-auto">
                    At Happy Tooth Dental Clinic, we prioritize your privacy and data security. Our website ensures
                    transparency in how we collect, use, and protect your personal information. We are committed to
                    maintaining your
                    trust and complying with all applicable regulations. This preview offers a glimpse into our policy
                    practices.
                </p>
            </div>

            <div class="text-center mt-4">
                <a href="web_policy.php" class="btn btn-info text-white px-4 py-2">Read Full Policy</a>
            </div>
        </div>
    </section>
    <section id="contact" class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="mb-3">Contact Information</h2>
                <hr>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="contact-info mx-auto">
                        <div class="info-item d-flex mb-4">
                            <div class="icon-circle me-3">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="info-text">
                                <strong>Address</strong><br>
                                7A, Jalan Bunga Melur 2/18, Seksyen 2, 40000 Shah Alam, Selangor
                            </div>
                        </div>

                        <div class="info-item d-flex mb-4">
                            <div class="icon-circle me-3">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div class="info-text">
                                <strong>Phone</strong><br>
                                (+60) 18-368 7133
                            </div>
                        </div>

                        <div class="info-item d-flex mb-4">
                            <div class="icon-circle me-3">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="info-text">
                                <strong>Email</strong><br>
                                info@happytooth.com
                            </div>
                        </div>

                        <div class="info-item d-flex mb-4">
                            <div class="icon-circle me-3">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="info-text">
                                <strong>Hours</strong><br>
                                Monday – Friday: 8:00 AM – 6:00 PM<br>
                                Saturday: 9:00 AM – 2:00 PM<br>
                                Sunday: Closed
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="map-responsive rounded shadow">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3984.4552468305016!2d101.49880037466827!3d3.064972796931584!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cdb0e000000001%3A0x87a8b8c8d8b8c8d8!2s7A%2C%20Jalan%20Bunga%20Melur%202%2F18%2C%20Seksyen%202%2C%2040000%20Shah%20Alam%2C%20Selangor!5e0!3m2!1sen!2smy!4v1718729548480!5m2!1sen!2smy"
                            width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const filterButtons = document.querySelectorAll('.filter-btn');
        const staffCards = document.querySelectorAll('.staff-card');

        function showCards(role) {
            staffCards.forEach(card => {
                const cardRole = card.getAttribute('data-role');
                card.style.display = (role === 'all' || role === cardRole) ? 'block' : 'none';
            });
        }

        window.addEventListener('DOMContentLoaded', () => {
            showCards('all');
        });

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const role = button.getAttribute('data-filter');

                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                showCards(role);
            });
        });
    </script>

</body>

</html>