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
    <title>Happy Tooth Dental Clinic - Web Policy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            line-height: 1.6;
            color: #333;
            background-color: #e0f7fa;
        }

        h2,
        h3 {
            color: #00c4cc;
        }

        h1 {
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            color: white;
        }

        p {
            margin-bottom: 1rem;
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

        .accordion {
            margin-top: 40px;
        }

        .accordion-item {
            border: 1px solid #dee2e6;
            margin-bottom: 10px;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInRise 0.8s ease-out forwards;
        }

        .accordion-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .accordion-header .accordion-button {
            background-color: #00c4cc;
            color: rgb(0, 90, 93);
            font-weight: 600;
            font-size: 1.2rem;
            padding: 1rem 1.25rem;
            border: none;
            box-shadow: none;
            border-radius: 0.5rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .accordion-header .accordion-button:not(.collapsed) {
            background-color: #00c4cc;
            color: white;
            border-bottom: 1px solid #009ea6;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .accordion-body {
            padding: 1.5rem;
            background-color: white;
            border-top: 1px solid #dee2e6;
            color: #555;
            font-size: 1rem;
        }

        .accordion-body h3 {
            font-weight: 600;
            font-size: 1.2rem;
            color: #008f94;
            margin-top: 1.2rem;
        }

        .accordion-body ul,
        .accordion-body ol {
            margin-left: 20px;
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .container {
            max-width: 960px;
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

        @keyframes fadeInRise {
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
            .page-header h1 {
                font-size: 2rem;
            }

            .page-header p {
                font-size: 0.9rem;
            }

            .accordion-header .accordion-button {
                font-size: 1rem;
                padding: 0.8rem 1rem;
            }

            .accordion-body {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <header class="page-header">
        <div class="container">
            <h1>Our Web Policy</h1>
            <p>Keeping you informed and your data safe.</p>
        </div>
    </header>

    <main class="py-5">
        <div class="container">
            <p class="policy-intro text-center">At Happy Tooth Dental Clinic, your trust is important to us. Here's a
                clear overview
                of our website policies, designed to be easy to understand and navigate. Click on each section to learn
                more.</p>

            <div class="accordion" id="policyAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingPrivacy">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapsePrivacy" aria-expanded="true" aria-controls="collapsePrivacy">
                            Privacy Policy
                        </button>
                    </h2>
                    <div id="collapsePrivacy" class="accordion-collapse collapse show" aria-labelledby="headingPrivacy"
                        data-bs-parent="#policyAccordion">
                        <div class="accordion-body">
                            <p><strong>Effective Date:</strong> June 16, 2025</p>
                            <p>At Happy Tooth Dental Clinic, we care about your privacy. This policy explains how we
                                collect, use, and share your personal information when you use our website and services.
                            </p>

                            <h3>1. What We Collect</h3>
                            <p>We collect information you give us when you:</p>
                            <ul>
                                <li>Book an appointment (name, contact details).</li>
                                <li>Sign up for a membership (address, email, phone).</li>
                                <li>Ask us questions through forms or email.</li>
                                <li>Fill out surveys.</li>
                            </ul>
                            <p>We also automatically collect basic data such as IP address, browser type, and how you
                                use our site, often through cookies.</p>

                            <h3>2. How We Use Your Info</h3>
                            <p>We use your information to:</p>
                            <ul>
                                <li>Manage your appointments and memberships.</li>
                                <li>Contact you about your visits and clinic news.</li>
                                <li>Make our website and services better.</li>
                                <li>Answer your questions and provide support.</li>
                                <li>Meet legal requirements.</li>
                            </ul>

                            <h3>3. Sharing Your Info</h3>
                            <p>We do not sell or trade your personal information. We only share it:</p>
                            <ul>
                                <li>With trusted partners who help us run our site and services, and they must keep it
                                    confidential.</li>
                                <li>If the law requires it (e.g., a court order).</li>
                                <li>To protect the rights and safety of our clinic or patients.</li>
                            </ul>

                            <h3>4. Data Security</h3>
                            <p>We use strong security measures to protect your personal information. This includes data
                                encryption, secure servers, and access controls to keep your data safe.</p>

                            <h3>5. Your Rights</h3>
                            <p>You may have rights to access, correct, delete, or limit the use of your personal
                                information. Contact us to learn more.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTerms">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseTerms" aria-expanded="false" aria-controls="collapseTerms">
                            Terms of Service
                        </button>
                    </h2>
                    <div id="collapseTerms" class="accordion-collapse collapse" aria-labelledby="headingTerms"
                        data-bs-parent="#policyAccordion">
                        <div class="accordion-body">
                            <p>These terms explain how you can use the Happy Tooth Dental Clinic website. By using our
                                site, you agree to these terms.</p>

                            <h3>1. Accepting Our Terms</h3>
                            <p>By using our website, you agree to follow these terms. If you don't agree, please don't
                                use our site.</p>

                            <h3>2. Changes to Terms</h3>
                            <p>We might update these terms. We'll post any major changes here. If you keep using the
                                site after changes, you accept the new terms.</p>

                            <h3>3. Using the Site</h3>
                            <ul>
                                <li>You must be at least 18 to book appointments or join memberships.</li>
                                <li>Use our site only for legal purposes and in a way that doesn't harm others.</li>
                                <li>Keep your account login details private. You're responsible for what happens on your
                                    account.</li>
                            </ul>

                            <h3>4. Our Content</h3>
                            <p>All content on this site (text, images, logos, etc.) belongs to Happy Tooth Dental Clinic
                                or our suppliers. You can't copy, share, or change any content without our written
                                permission.</p>

                            <h3>5. No Guarantees</h3>
                            <p>We provide this site "as is." We don't make any promises about its completeness or
                                accuracy.</p>

                            <h3>6. Our Responsibility</h3>
                            <p>Happy Tooth Dental Clinic is not responsible for any damages from using this site.</p>

                            <h3>7. Applicable Law</h3>
                            <p>These terms are governed by the laws of Malaysia.</p>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingDisclaimer">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseDisclaimer" aria-expanded="false"
                            aria-controls="collapseDisclaimer">
                            Disclaimer
                        </button>
                    </h2>
                    <div id="collapseDisclaimer" class="accordion-collapse collapse" aria-labelledby="headingDisclaimer"
                        data-bs-parent="#policyAccordion">
                        <div class="accordion-body">
                            <p>The information on Happy Tooth Dental Clinic's website is for general information only.
                                It is not medical advice. Always talk to a dental professional for medical advice,
                                diagnosis, or treatment.</p>

                            <ul>
                                <li><strong>No Doctor-Patient Relationship:</strong> Using this website doesn't mean you
                                    have a doctor-patient relationship with us.</li>
                                <li><strong>Get Professional Advice:</strong> Always consult a qualified dental
                                    professional for your dental health questions. Don't ignore professional advice
                                    because of something you read here.</li>
                                <li><strong>Accuracy:</strong> We try to keep information accurate, but we don't
                                    guarantee that all content is always correct or up-to-date.</li>
                                <li><strong>External Links:</strong> We may link to other websites for your convenience.
                                    We are not responsible for their content or privacy.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingCopyright">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseCopyright" aria-expanded="false" aria-controls="collapseCopyright">
                            Copyright Information
                        </button>
                    </h2>
                    <div id="collapseCopyright" class="accordion-collapse collapse" aria-labelledby="headingCopyright"
                        data-bs-parent="#policyAccordion">
                        <div class="accordion-body">
                            <p>All content on this website, including text, graphics, and images, is owned by Happy
                                Tooth Dental Clinic or our content suppliers and is protected by copyright laws.</p>
                            <p>You cannot copy, duplicate, sell, or use any part of this site for commercial purposes
                                without our written permission.</p>
                            <p>Any unauthorized use will end your right to use the site.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>