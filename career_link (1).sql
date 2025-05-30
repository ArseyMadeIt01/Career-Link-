-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2024 at 05:03 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `career_link`
--

-- --------------------------------------------------------

--
-- Table structure for table `employer_profiles`
--

CREATE TABLE `employer_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `company_description` text DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `company_size` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employer_profiles`
--

INSERT INTO `employer_profiles` (`id`, `user_id`, `company_name`, `company_description`, `industry`, `company_size`, `website`, `location`, `contact_email`, `contact_phone`, `created_at`, `updated_at`) VALUES
(1, 5, 'Cleveland Schneider Co', 'Nihil nesciunt unde', 'finance', '11-50', 'https://www.kepiqyginodysa.me.uk', 'Obcaecati sunt aper', 'bifumatuky@mailinator.com', '+1 (268) 276-8087', '2024-11-20 14:41:38', '2024-11-20 14:41:38'),
(2, 5, 'Tech Solutions Inc.', 'Leading technology solutions provider specializing in innovative software development and IT consulting.', 'technology', '51-200', 'https://techsolutions.com', 'New York, USA', 'careers@techsolutions.com', '+1234567890', '2024-11-20 15:14:42', '2024-11-20 15:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `employer_id` int(11) NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `job_type` enum('full-time','part-time','contract','internship') NOT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `posted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `deadline_date` date DEFAULT NULL,
  `status` enum('active','closed','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `employer_id`, `company_name`, `title`, `description`, `requirements`, `location`, `job_type`, `salary_range`, `posted_date`, `deadline_date`, `status`, `created_at`) VALUES
(1, 4, NULL, 'Repellendus Veritat', 'Consequuntur nihil e', 'Sunt culpa voluptas', 'Unde qui ipsam quo p', 'contract', 'Velit sed culpa mole', '2024-11-20 12:20:03', '2024-11-20', 'active', '2024-11-20 12:24:28'),
(2, 5, NULL, 'Excepturi enim nemo ', 'Aut laboriosam quam', 'Dolore ab porro veli', 'Velit nobis nesciunt', 'contract', 'Voluptatem obcaecati', '2024-11-20 14:30:38', '2024-11-20', 'active', '2024-11-20 14:30:38'),
(3, 5, NULL, 'Nisi qui delectus a', 'Excepteur sequi volu', 'Omnis qui dolor rati', 'Facilis sit alias q', 'internship', 'Dolorum quia dolor e', '2024-11-20 14:41:51', '2024-11-20', 'active', '2024-11-20 14:41:51'),
(4, 5, NULL, 'Senior Software Engineer', 'We are looking for an experienced software engineer to join our dynamic team. The ideal candidate will have strong programming skills and experience in developing scalable applications.', '- 5+ years experience in software development\n- Strong knowledge of Java, Python\n- Experience with cloud platforms\n- Bachelor\'s degree in Computer Science', 'New York, USA', 'full-time', '$120,000 - $150,000', '2024-11-20 15:14:43', '2024-12-31', 'active', '2024-11-20 15:14:43'),
(5, 5, NULL, 'Frontend Developer', 'Join our UI/UX team to create beautiful and responsive web applications. Work with modern frameworks and tools to deliver exceptional user experiences.', '- 3+ years frontend development experience\n- Expertise in React/Vue.js\n- Strong HTML, CSS, JavaScript skills\n- Experience with responsive design', 'Remote', 'full-time', '$80,000 - $100,000', '2024-11-20 15:14:43', '2024-12-25', 'active', '2024-11-20 15:14:43'),
(6, 5, NULL, 'DevOps Engineer', 'Looking for a skilled DevOps engineer to help streamline our development and deployment processes. Focus on automation and infrastructure optimization.', '- Experience with AWS/Azure\n- Knowledge of Docker, Kubernetes\n- CI/CD pipeline experience\n- Linux administration skills', 'New York, USA', 'full-time', '$100,000 - $130,000', '2024-11-20 15:14:43', '2024-12-20', 'active', '2024-11-20 15:14:43'),
(7, 5, NULL, 'UI/UX Designer', 'Creative designer needed to craft intuitive user interfaces and enhance user experiences across our product line.', '- 3+ years UI/UX design experience\n- Proficiency in Figma/Adobe XD\n- Portfolio showcasing web/mobile designs\n- User research experience', 'Remote', 'contract', '$70,000 - $90,000', '2024-11-20 15:14:43', '2024-12-15', 'active', '2024-11-20 15:14:43'),
(8, 5, NULL, 'Product Manager', 'Seeking an experienced product manager to lead product development initiatives and drive innovation.', '- 5+ years product management experience\n- Strong analytical skills\n- Excellent communication abilities\n- Agile methodology experience', 'New York, USA', 'full-time', '$110,000 - $140,000', '2024-11-20 15:14:43', '2024-12-28', 'active', '2024-11-20 15:14:43');

-- --------------------------------------------------------

--
-- Table structure for table `jobseeker_profiles`
--

CREATE TABLE `jobseeker_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `experience` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobseeker_profiles`
--

INSERT INTO `jobseeker_profiles` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `location`, `resume_path`, `skills`, `experience`, `education`, `created_at`, `updated_at`) VALUES
(1, 2, 'Test', 'User', 'jobseeker@test.com', '1234567890', NULL, NULL, NULL, NULL, NULL, '2024-11-20 12:29:03', '2024-11-20 12:29:03'),
(2, 3, 'Test', 'User', 'dovudamu@mailinator.com', '1234567890', NULL, NULL, NULL, NULL, NULL, '2024-11-20 12:29:03', '2024-11-20 12:29:03'),
(4, 6, 'Hanna', 'Owens', 'juxodu@mailinator.com', '+1 (986) 777-6275', 'Eu veniam aut accus', 'uploads/resumes/resume_6_1732110587.docx', 'Aut nihil ad qui des', 'Aut ducimus ut mole', 'Reiciendis eu assume', '2024-11-20 13:24:05', '2024-11-20 13:49:47');

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `jobseeker_id` int(11) NOT NULL,
  `application_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','reviewed','shortlisted','rejected') DEFAULT 'pending',
  `cover_letter` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`id`, `job_id`, `jobseeker_id`, `application_date`, `status`, `cover_letter`) VALUES
(1, 1, 6, '2024-11-20 13:50:13', 'pending', 'rszftddcc'),
(2, 1, 6, '2024-11-20 14:03:53', 'pending', 'rszftddcc'),
(3, 3, 6, '2024-11-20 14:48:42', 'reviewed', 'I\\\'m fit for this role. Employ me kindly'),
(4, 2, 3, '2024-11-01 21:00:00', 'rejected', 'I am very interested in this position and believe my skills and experience make me an ideal candidate.'),
(5, 3, 1, '2024-11-15 21:00:00', 'shortlisted', 'I am very interested in this position and believe my skills and experience make me an ideal candidate.'),
(6, 4, 4, '2024-10-31 21:00:00', 'shortlisted', 'I am very interested in this position and believe my skills and experience make me an ideal candidate.'),
(7, 5, 3, '2024-11-13 21:00:00', 'reviewed', 'I am very interested in this position and believe my skills and experience make me an ideal candidate.'),
(8, 6, 3, '2024-10-24 21:00:00', 'reviewed', 'I am very interested in this position and believe my skills and experience make me an ideal candidate.'),
(9, 7, 2, '2024-10-21 21:00:00', 'shortlisted', 'I am very interested in this position and believe my skills and experience make me an ideal candidate.'),
(10, 8, 2, '2024-11-08 21:00:00', 'pending', 'I am very interested in this position and believe my skills and experience make me an ideal candidate.');

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `jobseeker_id` int(11) NOT NULL,
  `saved_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_jobs`
--

INSERT INTO `saved_jobs` (`id`, `job_id`, `jobseeker_id`, `saved_date`) VALUES
(1, 1, 6, '2024-11-20 13:19:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('jobseeker','employer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `user_type`, `created_at`, `last_login`) VALUES
(1, 'testemployer', 'employer@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', '2024-11-20 11:41:21', NULL),
(2, 'testjobseeker', 'jobseeker@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jobseeker', '2024-11-20 11:41:21', NULL),
(3, 'zenej', 'dovudamu@mailinator.com', '$2y$10$YkrhKzomYzEMfjIF3XGJMOgct9vJeJzmwhymqzrGSidZ3IjXgdLTu', 'jobseeker', '2024-11-20 11:44:54', NULL),
(4, 'fabipeh', 'jukujaru@mailinator.com', '$2y$10$VI0TnVz0/OSDzNhg25ANcuRPiezkwFtQ1im6pNr8Jb8NjHLJkpqke', 'employer', '2024-11-20 12:17:12', NULL),
(5, 'haripedi', 'tahy@mailinator.com', '$2y$10$4MicpDlt7gtga2BAwLwTmednWmB3.lVQqAjvPD8vHgX4.97l0VQuu', 'employer', '2024-11-20 12:40:56', NULL),
(6, 'lavik', 'juxodu@mailinator.com', '$2y$10$.lzL2dQblVCFF/n.n4RVIum0.7MzRVNA.C3JL.b.8MCfN1QxbqRGG', 'jobseeker', '2024-11-20 12:41:57', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indexes for table `jobseeker_profiles`
--
ALTER TABLE `jobseeker_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `jobseeker_id` (`jobseeker_id`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `jobseeker_id` (`jobseeker_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `jobseeker_profiles`
--
ALTER TABLE `jobseeker_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD CONSTRAINT `employer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `jobseeker_profiles`
--
ALTER TABLE `jobseeker_profiles`
  ADD CONSTRAINT `jobseeker_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`jobseeker_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `saved_jobs_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  ADD CONSTRAINT `saved_jobs_ibfk_2` FOREIGN KEY (`jobseeker_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
