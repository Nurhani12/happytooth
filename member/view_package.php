<?php
session_start();
// Replace line 3 in edit_package.php with:
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
    <title>Happy Tooth Dental Clinic - Memberships</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-weight: 600;
        }

        p {
            margin-bottom: 1rem;
        }

        .page-header {
            background-color: #00c4cc;
            padding: 40px 0;
            text-align: center;
        }

        .page-header h1 {
            color: white;
            font-size: 2.2rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            animation: slideInDown 0.8s ease-out forwards;
        }

        .page-header p {
            font-size: 1rem;
            color: #e0f7fa;
            max-width: 700px;
            margin: 0 auto;
            animation: slideInDown 0.8s ease-out forwards;

        }

        .membership-section {
            padding: 60px 0;
        }

        .pricing-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .pricing-card.pro-plan {
            border: 3px solid #6b46e0;
            transform: scale(1.03);
            box-shadow: 0 15px 40px rgba(107, 70, 224, 0.2);
        }

        .pricing-card.pro-plan:hover {
            transform: translateY(-10px) scale(1.04);
            box-shadow: 0 20px 45px rgba(107, 70, 224, 0.3);
        }

        .pricing-card h3 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #333;
        }

        .pricing-card .subtitle {
            font-size: 1rem;
            color: #777;
            margin-bottom: 30px;
        }

        .pricing-card .price {
            font-size: 3.5rem;
            font-weight: 700;
            color: #00c4cc;
            margin-bottom: 20px;
            line-height: 1;
        }

        .pricing-card .price small {
            font-size: 1.2rem;
            font-weight: 500;
            color: #777;
        }

        .pricing-card.pro-plan .price {
            color: #6b46e0;
        }

        .pricing-card ul {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
            text-align: left;
            flex-grow: 1;
        }

        .pricing-card ul li {
            font-size: 1rem;
            color: #555;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .pricing-card ul li i.bi-check-circle-fill {
            color: #00c4cc;
            margin-right: 10px;
            font-size: 1.1em;
        }

        .pricing-card .btn {
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .pricing-card .btn-primary {
            background-color: #00c4cc;
            border-color: #00c4cc;
        }

        .pricing-card .btn-primary:hover {
            background-color: #009ea6;
            border-color: #009ea6;
            transform: translateY(-2px);
        }

        .pricing-card.pro-plan .btn-primary {
            background-color: #6b46e0;
            border-color: #6b46e0;
        }

        .pricing-card.pro-plan .btn-primary:hover {
            background-color: #512da8;
            border-color: #512da8;
        }

        .most-popular-badge {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #6b46e0;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            z-index: 10;
        }

        /* Animations */
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <div class="container">
            <h1>Choose Your Membership</h1>
            <p>Unlock exclusive benefits and enhanced dental care with our flexible membership plans.</p>
        </div>
    </header>

    <main class="membership-section">
        <div class="container">
            <div class="row justify-content-center g-4">

                <div class="col-md-6 col-lg-4 d-flex">
                    <div class="pricing-card">
                        <div>
                            <h3>Child</h3>
                            <p class="subtitle">For our young smiles (Ages 0-12)</p>
                            <h4 class="price">RM150<small>/year</small></h4>
                            <ul>
                                <li><i class="bi bi-check-circle-fill"></i> Free Consultation</li>
                                <li><i class="bi bi-check-circle-fill"></i> 1X Professional Cleaning</li>
                                <li><i class="bi bi-check-circle-fill"></i> 1X Regular Exam</li>
                                <li><i class="bi bi-check-circle-fill"></i> 1X Fluoride Treatment</li>
                                <li><i class="bi bi-check-circle-fill"></i> 1X Emergency Exam</li>
                                <li><i class="bi bi-check-circle-fill"></i> Routine X-Rays</li>
                            </ul>
                        </div>
                        <a href="./edit_package.php" class="btn btn-primary">Select Plan</a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 d-flex">
                    <div class="pricing-card pro-plan">
                        <div class="most-popular-badge">Most Popular</div>
                        <div>
                            <h3>Adult</h3>
                            <p class="subtitle">Comprehensive care for adults</p>
                            <h4 class="price">RM280<small>/year</small></h4>
                            <ul>
                                <li><i class="bi bi-check-circle-fill"></i> Free Consultation</li>
                                <li><i class="bi bi-check-circle-fill"></i> 2X Professional Cleaning</li>
                                <li><i class="bi bi-check-circle-fill"></i> 2X Regular Exam</li>
                                <li><i class="bi bi-check-circle-fill"></i> 2X Oral Screenings</li>
                                <li><i class="bi bi-check-circle-fill"></i> 1X Emergency Exam</li>
                                <li><i class="bi bi-check-circle-fill"></i> Routine X-Rays</li>
                            </ul>
                        </div>
                        <a href="./edit_package.php" class="btn btn-primary">Select Plan</a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 d-flex">
                    <div class="pricing-card">
                        <div>
                            <h3>Family</h3>
                            <p class="subtitle">Max 4 Family Members</p>
                            <h4 class="price">RM850<small>/year</small></h4>
                            <ul>
                                <li><i class="bi bi-check-circle-fill"></i> Free Consultation</li>
                                <li><i class="bi bi-check-circle-fill"></i> 4X Professional Cleaning</li>
                                <li><i class="bi bi-check-circle-fill"></i> 4X Regular Exam</li>
                                <li><i class="bi bi-check-circle-fill"></i> 2X Fluoride Treatment</li>
                                <li><i class="bi bi-check-circle-fill"></i> 2X Oral Screenings</li>
                                <li><i class="bi bi-check-circle-fill"></i> 2X Emergency Exam</li>
                                <li><i class="bi bi-check-circle-fill"></i> Routine X-Rays for All Members</li>
                            </ul>
                        </div>
                        <a href="./edit_package.php" class="btn btn-primary">Select Plan</a>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>