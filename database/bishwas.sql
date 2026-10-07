-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 07, 2026 at 12:21 PM
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
-- Database: `bishwas`
--

-- --------------------------------------------------------

--
-- Table structure for table `about_vision`
--

CREATE TABLE `about_vision` (
  `id` int(11) NOT NULL,
  `top_subtitle` varchar(255) NOT NULL,
  `main_title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `point_1` varchar(255) NOT NULL,
  `point_2` varchar(255) NOT NULL,
  `point_3` varchar(255) NOT NULL,
  `quote_badge` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'Published',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_vision`
--

INSERT INTO `about_vision` (`id`, `top_subtitle`, `main_title`, `description`, `point_1`, `point_2`, `point_3`, `quote_badge`, `image`, `status`, `updated_at`) VALUES
(1, 'আমাদের লক্ষ্য ও উদ্দেশ্য', 'একটি আদর্শ ও আত্মনির্ভরশীল সমাজ বিনির্মাণ', 'বিশ্বাস এডুকেশন ফাউন্ডেশন (Bishwas Education Foundation.) একটি সম্পূর্ণ অরাজনৈতিক ও জনকল্যাণমূলক সেবা সংস্থা। সমাজের অবহেলিত ও দরিদ্র শ্রেণীর মানুষের মৌলিক চাহিদা পূরণ এবং তাদের কারিগরি শিক্ষার মাধ্যমে স্বাবলম্বী করে তোলাই আমাদের মূল ব্রত।', 'স্বচ্ছ ও জবাবদিহিতামূলক তহবিল বণ্টন ব্যবস্থা।', 'জ্ঞান, নৈতিকতা ও মানবিক মূল্যবোধের বিকাশ।', 'দক্ষতা বৃদ্ধি ও বেকারত্ব দূরীকরণে প্রশিক্ষণ ইনস্টিটিউট।', 'মানব সেবাই ইসলামের মূল শিক্ষা।', 'public/about/vision_1788346365_6a97fffd438df.jpg', 'Published', '2026-09-02 11:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `badge_text` varchar(100) DEFAULT 'নিয়মিত কার্যক্রম',
  `image` varchar(255) NOT NULL,
  `status` enum('active','draft') DEFAULT 'active',
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `title`, `badge_text`, `image`, `status`, `description`, `created_at`) VALUES
(2, 'New Project', 'নিয়মিত কার্যক্রম', 'public/project_img/1788005496_6a92cc7814f62.png', 'active', 'Reguler Project', '2026-08-29 12:11:36'),
(3, 'fsdfsffsdfs', 'নিয়মিত কার্যক্রম', 'public/project_img/1788071744_6a93cf4059143.png', 'active', 'fsdfsdfsffsdffffffffffffffffff', '2026-08-30 06:35:44'),
(5, 'নিয়মিত কার্যক্রম', 'নিয়মিত কার্যক্রম', 'public/project_img/1788335182_6a97d44eba423.png', 'active', 'নিয়মিত কার্যক্রম', '2026-09-02 07:46:22');

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE `blogs` (
  `id` int(11) NOT NULL,
  `blog_title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'ব্লগ',
  `publish_date` varchar(100) NOT NULL,
  `blog_image` varchar(255) DEFAULT NULL,
  `short_description` text NOT NULL,
  `full_content` longtext DEFAULT NULL,
  `status` enum('active','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blogs`
--

INSERT INTO `blogs` (`id`, `blog_title`, `category`, `publish_date`, `blog_image`, `short_description`, `full_content`, `status`, `created_at`) VALUES
(3, 'আমাদের ব্লগসমূহ ও ডায়েরি', 'ব্লগ', '৩ সেপ্টেম্বর, ২০২৬', 'public/blogs_img/1788435909_6a995dc5d8b64.jpg', 'আমাদের মাঠপর্যায়ের কাজের আপডেট, ডকুমেন্টারি এবং সচেতনতামূলক বিভিন্ন ভিডিও ও নিবন্ধগুলো নিচে দেখে নিন।', '', 'active', '2026-09-03 10:59:06');

-- --------------------------------------------------------

--
-- Table structure for table `branding_settings`
--

CREATE TABLE `branding_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `site_title` varchar(255) DEFAULT 'Bishwas Education Foundation',
  `site_logo` varchar(255) DEFAULT 'public/assets/logo_BG.png',
  `favicon_icon` varchar(255) DEFAULT 'public/assets/logo.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branding_settings`
--

INSERT INTO `branding_settings` (`id`, `site_title`, `site_logo`, `favicon_icon`) VALUES
(1, 'Bishwas Education Foundation', 'logo_1788594970.png', 'favicon_1788594970.png');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(10) UNSIGNED NOT NULL,
  `cert_no` varchar(30) NOT NULL DEFAULT '',
  `user_id` int(11) DEFAULT NULL,
  `recipient_name` varchar(150) NOT NULL,
  `title` varchar(200) NOT NULL,
  `event_name` varchar(200) NOT NULL DEFAULT '',
  `organization` varchar(200) NOT NULL DEFAULT '',
  `supported_by` varchar(200) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `theme` varchar(20) NOT NULL DEFAULT 'classic',
  `issue_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `cert_no`, `user_id`, `recipient_name`, `title`, `event_name`, `organization`, `supported_by`, `description`, `theme`, `issue_date`, `created_at`) VALUES
(1, 'CERT-1985-00001', 10, 'Md Tarek Rahman', 'Autem eiusmod accusa', 'Charlotte Watts', 'Finch and Michael Inc', 'Ea aperiam inventore', 'Eum et sit vel ut v', 'emerald', '1985-01-21', '2026-10-06 10:20:41'),
(2, 'CERT-2026-00002', 15, 'Duncan Moody', 'Cycle Reching', 'Independent Day', 'Bishwas Education Foundation', 'BK TECH 24', 'This is test', 'royal', '2026-10-06', '2026-10-06 10:23:02');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `created_at`) VALUES
(1, 'Bilash Kumar', 'bilash@gmail.com', '43353453453', 'zakat', 'fsdfsfsfsf', '2026-09-05 08:14:58'),
(2, 'Bilash Kumar', 'bilash@gmail.com', '43353453453', 'zakat', 'fsdfsfsfsf', '2026-09-05 08:17:20'),
(3, 'Bilash Kumar', 'bilash@gmail.com', '43353453453', 'zakat', 'fsdfsfsfsf', '2026-09-05 08:17:25'),
(4, 'Gokul', 'gokul@gmail.com', '0173425783', 'other', 'I am related this name of success', '2026-09-06 09:55:53'),
(5, 'Gokul', 'gokul@gmail.com', '0173425783', 'other', 'I am related this name of success', '2026-09-06 09:58:18'),
(6, 'referf', 'fef@gmail.com', '3455345', 'donation', 'rr33r3', '2026-09-08 06:30:22'),
(7, 'fsdfsf', 'fsfsfs@gmail.com', '4324234234', 'other', 'rwerwer', '2026-09-09 05:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `contact_settings`
--

CREATE TABLE `contact_settings` (
  `id` int(11) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text NOT NULL,
  `office_address` text NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `google_map_url` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_settings`
--

INSERT INTO `contact_settings` (`id`, `section_title`, `section_subtitle`, `office_address`, `phone_number`, `email_address`, `google_map_url`, `updated_at`) VALUES
(1, 'আমাদের সাথে যোগাযোগ করুন', 'আপনার যেকোনো জিজ্ঞাসা, পরামর্শ বা মতামতের জন্য আমাদের মেসেজ পাঠাতে পারেন। আমাদের প্রতিনিধি দ্রুত আপনার সাথে যোগাযোগ করবেন।', '১/জি/১০/১, মীরবাগ হাতিরঝিল, নতুন রাস্তা, ৩ নং লেন, ঢাকা-১২১৭, বাংলাদেশ', '+৮৮০ ১৭১৫-৪৮২৩৬৩', 'info@bishwas.org', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241.79815276802313!2d90.4128057552314!3d23.76047066860609!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b9e214dcf989%3A0x38ba85b6e6cbed80!2sBag%20Abdul!5e0!3m2!1sen!2sbd!4v1784616353959!5m2!1sen!2sbd', '2026-09-05 07:07:53');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `type` enum('Public','Member','Volunteer') DEFAULT 'Public',
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `donation_type` varchar(100) DEFAULT 'General',
  `fund` varchar(100) DEFAULT 'General Fund',
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `payment_status` enum('Pending','Paid','Failed','Rejected') DEFAULT 'Pending',
  `donation_date` date NOT NULL DEFAULT current_timestamp(),
  `admin_note` text DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `donor_id`, `type`, `name`, `email`, `phone`, `amount`, `donation_type`, `fund`, `payment_method`, `transaction_id`, `payment_status`, `donation_date`, `admin_note`, `receipt`, `created_at`, `updated_at`) VALUES
(4, NULL, 'Public', 'Abel Hoffman', 'riwemive@mailinator.com', '+1 (469) 619-2641', 51, 'General', 'General Fund', 'Nagad', 'Aspernatur velit com', 'Failed', '2026-09-12', '', '1789215427_2331.png', '2026-09-12 12:17:07', '2026-10-04 06:36:42'),
(6, 82, '', 'Drake Kline', 'binimurop@mailinator.com', '+1 (714) 299-9814', 26, 'Rerum eiusmod ut sit', 'Omnis ea esse conse', 'Dolore esse eum mini', 'Est eveniet quia ut', 'Rejected', '2005-11-01', 'Vero occaecat fugit', 'Nostrud dolore verit', '2026-10-01 11:59:50', '2026-10-04 06:34:22'),
(7, NULL, 'Public', 'Porter Jensen', 'qywin@mailinator.com', '+1 (822) 309-4316', 5000, 'General', 'General Fund', 'Nagad', 'Eaque sit natus qui', 'Pending', '2026-10-03', NULL, '1791010912_5108.png', '2026-10-03 07:01:52', '2026-10-03 07:01:52'),
(8, 2, 'Member', 'Unity Buchanan', 'picyvyzo@mailinator.com', '01786588675', 23434, 'Monthly', 'General Fund', 'Rocket', '', 'Paid', '2026-06-03', 'sfsfsf', '', '2026-10-03 11:14:36', '2026-10-06 06:35:43'),
(9, 1, 'Member', 'Kenneth Andrews', 'admin@gmail.com', '01709897865', 444, 'Zakat', 'General Fund', 'Nagod', '5353535', 'Pending', '2026-10-03', 'etetete', '', '2026-10-03 11:15:43', '2026-10-04 06:34:09'),
(11, 2, 'Member', 'Unity Buchanan', 'picyvyzo@mailinator.com', '01786588675', 80, 'General', 'General Fund', 'Cash', 'Itaque occaecat nisi', 'Paid', '2026-05-24', 'Fuga Provident ani', '', '2026-10-03 11:20:32', '2026-10-06 06:35:52'),
(12, 3, 'Member', 'Christen Davenport', 'tafu@mailinator.com', '01689786567', 66, 'General', 'নিয়মিত অনুদান তহবিল', 'SureCash', 'Id nemo unde ullamco', 'Pending', '2026-10-04', 'Aut cupidatat cupida', '', '2026-10-03 11:20:50', '2026-10-04 06:57:02'),
(13, 2, 'Member', 'Unity Buchanan', 'picyvyzo@mailinator.com', '01786588675', 91, 'Monthly', 'সাধারণ তহবিল', 'Upay', 'ggggggggggggggggg', 'Pending', '2026-01-03', 'Alias modi suscipit', '1791026947_9584.png', '2026-10-03 11:29:07', '2026-10-06 06:34:12'),
(21, 9, 'Member', 'Reece Cotton', 'xenimiw@mailinator.com', '01784675345', 5000, 'Monthly', 'সাধারণ তহবিল', 'Bank', 'Nam repudiandae vita', 'Pending', '2015-09-24', 'Sint sint quis vel d', '', '2026-10-04 10:17:36', '2026-10-04 10:18:48');

-- --------------------------------------------------------

--
-- Table structure for table `donation_sectors`
--

CREATE TABLE `donation_sectors` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `icon_class` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `button_text` varchar(100) NOT NULL,
  `button_link` varchar(255) DEFAULT '#',
  `status` enum('active','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donation_sectors`
--

INSERT INTO `donation_sectors` (`id`, `title`, `icon_class`, `description`, `button_text`, `button_link`, `status`, `created_at`) VALUES
(1, 'জরুরি ত্রাণ তহবিল', 'fa-solid fa-kit-medical', 'বন্যা, ঝড় কিংবা যেকোনো প্রাকৃতিক দুর্যোগে ক্ষতিগ্রস্ত অসহায় মানুষের পাশে...', 'অনুদানে শরীক হোন', '/donate-relief', 'active', '2026-08-31 06:36:13'),
(2, 'যাকাত তহবিল', 'fa-solid fa-coins', 'সম্পূর্ণ শরীয়াহ সম্মত উপায়ে আপনার যাকাত সংগ্রহ করে তা দরিদ্র পরিবারের...', 'যাকাত দিন', '/zakat', 'active', '2026-08-31 06:36:13'),
(3, 'নিয়মিত অনুদান তহবিল', 'fa-solid fa-calendar-check', 'প্রতি মাসে বা সপ্তাহে নির্দিষ্ট অংকের টাকা স্বয়ংক্রিয়ভাবে দেওয়ার সুবিধা...', 'নিয়মিত দাত হন', '', 'active', '2026-08-31 06:36:13'),
(4, 'সাধারণ তহবিল', 'fa-solid fa-box-archive', 'ফাউন্ডেশনের প্রশাসনিক খরচ, জনকল্যাণমূলক বহুমুখী প্রজেক্ট পরিচালনায়...', 'সাধারণ অনুদান', '/general-donate', 'active', '2026-08-31 06:36:13');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'Other',
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  `location` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `actual_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expected_beneficiaries` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `max_volunteers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `organizer` varchar(150) NOT NULL DEFAULT '',
  `contact_phone` varchar(20) NOT NULL DEFAULT '',
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `category`, `description`, `image`, `location`, `start_date`, `end_date`, `start_time`, `end_time`, `budget`, `actual_cost`, `expected_beneficiaries`, `max_volunteers`, `organizer`, `contact_phone`, `status`, `created_at`, `updated_at`) VALUES
(3, 'Sunt sint ipsa du', 'Medical Camp', 'Alias Nam lorem rem', '', 'Nulla laudantium eo', '2026-10-06', '2026-10-15', '01:05:00', '23:05:00', 50.00, 50.00, 87, 61, 'Laborum Obcaecati e', '+1 (393) 806-4132', 'Completed', '2026-10-06 05:45:38', '2026-10-06 06:21:46'),
(4, 'Dolores reprehenderi', 'Relief', 'Do deserunt nulla qu', '', 'Sint pariatur Cupi', '2026-12-29', '2026-12-29', '16:01:00', '18:12:00', 100.00, 80.00, 93, 89, 'Aut magni veniam in', '+1 (165) 351-8332', 'Completed', '2026-10-06 06:41:06', '2026-10-06 06:41:06'),
(5, 'Id quam et quo sint', 'Other', 'Ea ex dolor impedit', '', 'Proident cumque pos', '2026-02-02', '2026-04-04', '12:07:00', '21:27:00', 50.00, 500.00, 5, 33, 'Lorem sed soluta des', '+1 (667) 564-6401', 'Completed', '2026-10-06 06:45:07', '2026-10-06 06:46:20');

-- --------------------------------------------------------

--
-- Table structure for table `event_volunteers`
--

CREATE TABLE `event_volunteers` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `voucher_no` varchar(20) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'Other',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expense_date` date NOT NULL,
  `activity_id` int(10) UNSIGNED DEFAULT NULL,
  `recipient_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_method` varchar(40) NOT NULL DEFAULT '',
  `transaction_id` varchar(80) NOT NULL DEFAULT '',
  `paid_to` varchar(150) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `receipt` varchar(255) NOT NULL DEFAULT '',
  `status` enum('Pending','Complete') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `voucher_no`, `title`, `category`, `amount`, `expense_date`, `activity_id`, `recipient_id`, `payment_method`, `transaction_id`, `paid_to`, `description`, `receipt`, `status`, `created_at`, `updated_at`) VALUES
(2, 'EXP-000002', 'Molestias totam sint', 'Shelter', 38.00, '2026-09-27', 5, NULL, 'Upay', 'Illum sint dolor al', 'Ullam et quo et cons', 'Dolorum autem ut exe', '', 'Pending', '2026-10-05 10:55:17', '2026-10-06 08:46:09'),
(4, 'EXP-000004', 'Sed ex asperiores an', 'Education', 30.00, '2026-09-30', 3, NULL, 'Cash', 'Repudiandae dicta ac', 'Sed Nam in in numqua', 'Consequuntur dolores', '', 'Complete', '2026-10-06 06:38:34', '2026-10-06 06:38:54');

-- --------------------------------------------------------

--
-- Table structure for table `galleries`
--

CREATE TABLE `galleries` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `status` enum('active','draft') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `galleries`
--

INSERT INTO `galleries` (`id`, `image`, `caption`, `category`, `status`, `created_at`) VALUES
(1, 'public/gallery/1788351691_6a9814cb2101c.jpg', 'sdfasfsdfa', 'শিক্ষা তহবিল', 'active', '2026-09-02 12:21:31'),
(2, 'public/gallery/1788351832_6a981558d903d.JPG', 'sdfasfsdfa', 'জরুরি ত্রাণ তহবিল', 'active', '2026-09-02 12:23:52');

-- --------------------------------------------------------

--
-- Table structure for table `hero_settings`
--

CREATE TABLE `hero_settings` (
  `id` int(11) NOT NULL,
  `badge_text` varchar(255) DEFAULT 'মানবসেবায় একটি বিশ্বস্তযোগ্য প্রতিষ্ঠান',
  `heading_title` varchar(255) DEFAULT 'জন স্বার্থে,',
  `heading_highlight` varchar(255) DEFAULT 'বিশ্বাস ও আস্থার সাথে।',
  `description` text DEFAULT NULL,
  `cta_primary_text` varchar(100) DEFAULT 'আজই শরীক হোন',
  `cta_secondary_text` varchar(100) DEFAULT 'আমাদের লক্ষ্য জানুন',
  `stat_1_number` varchar(50) DEFAULT '১৭০০+',
  `stat_1_label` varchar(100) DEFAULT 'উপকারভোগী মানুষ',
  `stat_2_number` varchar(50) DEFAULT '১০+',
  `stat_2_label` varchar(100) DEFAULT 'সক্রিয় প্রজেক্ট',
  `stat_3_number` varchar(50) DEFAULT '১০০%',
  `stat_3_label` varchar(100) DEFAULT 'স্বচ্ছতা ও আমানত',
  `stat_4_number` varchar(50) DEFAULT '১০০+',
  `stat_4_label` varchar(100) DEFAULT 'নিবন্ধিত ভলান্টিয়ার',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hero_settings`
--

INSERT INTO `hero_settings` (`id`, `badge_text`, `heading_title`, `heading_highlight`, `description`, `cta_primary_text`, `cta_secondary_text`, `stat_1_number`, `stat_1_label`, `stat_2_number`, `stat_2_label`, `stat_3_number`, `stat_3_label`, `stat_4_number`, `stat_4_label`, `updated_at`) VALUES
(1, 'মানবসেবায় একটি বিশ্বস্তযোগ্য প্রতিষ্ঠান', 'জন স্বার্থে,', 'বিশ্বাস ও আস্থার সাথে।', 'বিশ্বাস এডুকেশন ফাউন্ডেশন একটি অলাভজনক ও সম্পূর্ণ দাতব্য সংস্থা যা মানুষের কল্যাণ, শিক্ষা বিস্তার, ও দুস্থদের কর্মসংস্থান তৈরিতে নিরলসভাবে কাজ করে যাচ্ছে। আপনার একটি ছোট অনুদান বদলে দিতে পারে একটি অসহায় পরিবারের ভাগ্য।', 'আজই শরীক হোন', 'আমাদের লক্ষ্য জানুন', '1700', 'উপকারভোগী মানুষ', '10', 'সক্রিয় প্রজেক্ট', '100', 'স্বচ্ছতা ও আমানত', '100', 'নিবন্ধিত ভলান্টিয়ার', '2026-08-31 04:33:16');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`, `created_at`) VALUES
(1, 'Food & Relief', '2026-10-06 11:21:01'),
(2, 'Clothing & Blankets', '2026-10-06 11:21:01'),
(3, 'Medical Supplies', '2026-10-06 11:21:01'),
(4, 'Education Materials', '2026-10-06 11:21:01'),
(5, 'Office Supplies', '2026-10-06 11:21:01'),
(6, 'Equipment & Tools', '2026-10-06 11:21:01'),
(7, 'Other', '2026-10-06 11:21:01');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `sku` varchar(40) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  `unit` varchar(30) NOT NULL DEFAULT 'pcs',
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_stock` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `location` varchar(150) NOT NULL DEFAULT '',
  `supplier` varchar(150) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `category_id`, `name`, `sku`, `description`, `image`, `unit`, `quantity`, `min_stock`, `unit_price`, `location`, `supplier`, `created_at`, `updated_at`) VALUES
(2, 1, 'fsdfs', 'fsf', 'fsfsf', '', 'pcs', 232.00, 5.00, 37.00, 'sfdfsf', 'fsdf', '2026-10-06 12:02:40', '2026-10-06 12:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `qty` decimal(12,2) NOT NULL,
  `note` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `item_id`, `type`, `qty`, `note`, `created_at`) VALUES
(4, 2, 'IN', 232.00, 'Opening stock', '2026-10-06 12:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(10) UNSIGNED NOT NULL,
  `meeting_no` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `agenda` text DEFAULT NULL,
  `category` varchar(60) NOT NULL DEFAULT 'General',
  `meeting_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `mtype` enum('Online','Offline','Hybrid') NOT NULL DEFAULT 'Offline',
  `link` varchar(500) NOT NULL DEFAULT '',
  `address` varchar(500) NOT NULL DEFAULT '',
  `organizer` varchar(150) NOT NULL DEFAULT '',
  `audience` set('Admin','General Member','Associate Member','Life Member','Volunteer Member') NOT NULL,
  `status` enum('Scheduled','Ongoing','Completed','Postponed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `minutes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`id`, `meeting_no`, `title`, `agenda`, `category`, `meeting_date`, `start_time`, `end_time`, `mtype`, `link`, `address`, `organizer`, `audience`, `status`, `minutes`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'MTG-2026-00001', 'this is title', 'title one \r\ntitle two', 'Annual General Meeting', '2026-10-16', '15:29:00', '17:32:00', 'Offline', '', 'Gulshan - 1', 'Bilash Kumar', 'Admin,General Member,Associate Member,Life Member,Volunteer Member', 'Completed', 'this is  test description', 6, 6, '2026-10-07 07:30:01', '2026-10-07 07:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `meeting_editors`
--

CREATE TABLE `meeting_editors` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `granted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meeting_editors`
--

INSERT INTO `meeting_editors` (`id`, `user_id`, `granted_by`, `created_at`) VALUES
(1, 15, 6, '2026-10-07 07:27:17');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(10) UNSIGNED NOT NULL,
  `ref_no` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `recipient` varchar(500) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'General',
  `priority` enum('Normal','Important','Urgent') NOT NULL DEFAULT 'Normal',
  `audience` set('Admin','General Member','Associate Member','Life Member','Volunteer Member') NOT NULL,
  `signatory_name` varchar(150) NOT NULL DEFAULT '',
  `signatory_title` varchar(150) NOT NULL DEFAULT '',
  `contact` varchar(150) NOT NULL DEFAULT '',
  `attachment` varchar(255) NOT NULL DEFAULT '',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Published','Draft') NOT NULL DEFAULT 'Published',
  `publish_date` date NOT NULL,
  `expire_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `ref_no`, `title`, `recipient`, `body`, `category`, `priority`, `audience`, `signatory_name`, `signatory_title`, `contact`, `attachment`, `is_pinned`, `status`, `publish_date`, `expire_date`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'NTC-2026-00001', 'this is subject', 'All Members', 'Dear ,\r\nthis is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section this is a test dashbaord section', 'General', 'Important', 'Admin,General Member,Associate Member,Life Member,Volunteer Member', 'Bilash Kumar', 'Web Developer', '01787673543', 'ntc_1791357118_2650.png', 0, 'Published', '2026-10-07', '2026-10-08', 6, NULL, '2026-10-07 07:11:58', '2026-10-07 07:11:58');

-- --------------------------------------------------------

--
-- Table structure for table `notice_editors`
--

CREATE TABLE `notice_editors` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `granted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notice_editors`
--

INSERT INTO `notice_editors` (`id`, `user_id`, `granted_by`, `created_at`) VALUES
(1, 15, 6, '2026-10-07 07:09:21');

-- --------------------------------------------------------

--
-- Table structure for table `portal_settings`
--

CREATE TABLE `portal_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_recipients`
--

CREATE TABLE `service_recipients` (
  `id` int(10) UNSIGNED NOT NULL,
  `serial_no` varchar(20) NOT NULL DEFAULT '',
  `member_name` varchar(150) NOT NULL,
  `mother_name` varchar(150) NOT NULL DEFAULT '',
  `father_husband_name` varchar(150) NOT NULL DEFAULT '',
  `dob` date DEFAULT NULL,
  `gender` varchar(10) NOT NULL DEFAULT '',
  `nid_no` varchar(30) NOT NULL DEFAULT '',
  `birth_cert_no` varchar(30) NOT NULL DEFAULT '',
  `present_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `blood_group` varchar(5) NOT NULL DEFAULT '',
  `height` varchar(20) NOT NULL DEFAULT '',
  `disability_type` varchar(100) NOT NULL DEFAULT '',
  `other_info` text DEFAULT NULL,
  `financial_status` varchar(60) NOT NULL DEFAULT '',
  `social_status` varchar(60) NOT NULL DEFAULT '',
  `mobile_no` varchar(20) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL DEFAULT '',
  `application_date` date DEFAULT NULL,
  `membership_no` varchar(50) NOT NULL DEFAULT '',
  `registration_no` varchar(50) NOT NULL DEFAULT '',
  `remarks` text DEFAULT NULL,
  `photo` varchar(255) NOT NULL DEFAULT '',
  `guardian_photo` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `service_recipients`
--

INSERT INTO `service_recipients` (`id`, `serial_no`, `member_name`, `mother_name`, `father_husband_name`, `dob`, `gender`, `nid_no`, `birth_cert_no`, `present_address`, `permanent_address`, `blood_group`, `height`, `disability_type`, `other_info`, `financial_status`, `social_status`, `mobile_no`, `email`, `application_date`, `membership_no`, `registration_no`, `remarks`, `photo`, `guardian_photo`, `created_at`, `updated_at`) VALUES
(1, 'SR-000001', 'Aiko Mason', 'Hector Hamilton', 'Timothy Hopper', '1987-03-02', 'Female', 'Pariatur Aut dolore', 'Consectetur dolor m', 'Consequuntur necessi', 'Consequuntur necessi', 'A-', 'Adipisicing id quo q', 'Non at voluptate bea', 'Provident fugit in', 'Laudantium eligendi', 'Occaecat minus id id', '01787675645', 'hucygipepo@mailinator.com', '1996-01-10', 'Ipsa voluptatum et', 'Temporibus nesciunt', 'Sapiente deleniti il', 'rec_1791198942_8495.png', 'grd_1791198942_6235.png', '2026-10-05 11:15:42', '2026-10-05 11:15:42');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `donate_btn_text` varchar(255) DEFAULT 'অনুদান দিন',
  `footer_about_text` text DEFAULT NULL,
  `footer_social_title` text DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT '',
  `youtube_url` varchar(255) DEFAULT '',
  `twitter_url` varchar(255) DEFAULT '',
  `linkedin_url` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `donate_btn_text`, `footer_about_text`, `footer_social_title`, `facebook_url`, `youtube_url`, `twitter_url`, `linkedin_url`) VALUES
(1, 'অনুদান দিন', 'একটি স্বচ্ছ, নির্ভরযোগ্য ও অলাভজনক দাতব্য প্রতিষ্ঠান, যা মানবতার কল্যাণ ও ইসলামের সুমহান আদর্শ প্রসারে কাজ করছে।', 'আমাদের কাজের সর্বশেষ আপডেট জানতে যুক্ত থাকুন।', 'https://facebook.com/bishwas', 'https://youtube.com/bishwas', 'https://twitter.com/bishwas', 'https://linkedin.com/company/bishwas');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `status` enum('To Do','In Progress','In Review','On Hold','Completed','Cancelled') NOT NULL DEFAULT 'To Do',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `project_id`, `priority`, `status`, `progress`, `start_date`, `due_date`, `created_by`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'most', 'descrpiont', 5, 'Medium', 'In Progress', 30, '2026-10-06', '2026-10-31', 6, NULL, '2026-10-06 11:44:01', '2026-10-06 11:53:34');

-- --------------------------------------------------------

--
-- Table structure for table `task_assignees`
--

CREATE TABLE `task_assignees` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_assignees`
--

INSERT INTO `task_assignees` (`id`, `task_id`, `user_id`, `assigned_at`) VALUES
(1, 1, 15, '2026-10-06 11:44:01');

-- --------------------------------------------------------

--
-- Table structure for table `task_updates`
--

CREATE TABLE `task_updates` (
  `id` int(10) UNSIGNED NOT NULL,
  `task_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT '',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `note` varchar(500) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_updates`
--

INSERT INTO `task_updates` (`id`, `task_id`, `user_id`, `status`, `progress`, `note`, `created_at`) VALUES
(1, 1, 6, 'To Do', 0, 'Task created', '2026-10-06 11:44:01'),
(3, 1, 15, 'To Do', 30, 'first step done', '2026-10-06 11:52:37'),
(4, 1, 6, 'In Progress', 30, '', '2026-10-06 11:53:35');

-- --------------------------------------------------------

--
-- Table structure for table `terms_conditions`
--

CREATE TABLE `terms_conditions` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terms_conditions`
--

INSERT INTO `terms_conditions` (`id`, `title`, `content`, `status`, `sort_order`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'this is short title', 'this this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test descriptionthis this test description', 'Active', 1, 6, '2026-10-07 08:41:51', '2026-10-07 10:18:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `member_name` varchar(150) NOT NULL,
  `mother_name` varchar(150) NOT NULL DEFAULT '',
  `father_husband_name` varchar(150) NOT NULL DEFAULT '',
  `dob` date DEFAULT NULL,
  `gender` varchar(20) NOT NULL DEFAULT '',
  `id_type` varchar(30) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `qualification` varchar(150) DEFAULT NULL,
  `mobile_no` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `present_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `other_info` varchar(255) DEFAULT NULL,
  `user_type` enum('Admin','General Member','Associate Member','Life Member','Volunteer Member') DEFAULT 'General Member',
  `status` enum('Active','Pending') NOT NULL DEFAULT 'Pending',
  `password` varchar(32) NOT NULL DEFAULT '827ccb0eea8a706c4c34a16891f84e7b',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `photo`, `member_name`, `mother_name`, `father_husband_name`, `dob`, `gender`, `id_type`, `id_number`, `qualification`, `mobile_no`, `email`, `present_address`, `permanent_address`, `other_info`, `user_type`, `status`, `password`, `created_at`) VALUES
(6, '', 'Admin User', 'Brenna Hewitt', 'Wylie Caldwell', '2018-09-28', 'Female', 'Birth Certificate', '798', 'Aliquam sapiente cor', '01787675644', 'admin@gmail.com', 'Ut perspiciatis con', 'Reprehenderit qui a', 'Sed dolore quae vero', 'Admin', 'Active', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-04 07:44:33'),
(8, '', 'Kenneth Pierce', 'Amanda Savage', 'Dale Rodriquez', '1979-04-03', 'Female', 'NID', '940', 'Molestiae voluptate', '01787665645', 'fukuc@mailinator.com', 'Cupiditate itaque om', 'Repellendus Sunt re', 'Est et ipsam ea quo', 'Associate Member', 'Pending', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-04 07:49:08'),
(9, '', 'Reece Cotton', 'Macaulay Sampson', 'Hector Sellers', '2025-06-22', 'Other', 'Birth Certificate', '195', 'Cumque laudantium a', '01784675345', 'xenimiw@mailinator.com', 'Tempore id atque a', 'Sit culpa ut volupta', 'Facere magna placeat', 'Life Member', 'Pending', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-04 07:49:27'),
(10, '', 'Isaac Guy', 'Sasha Slater', 'Keane Tanner', '1997-12-28', 'Male', 'NID', '169', 'Fugiat molestiae sa', '01784675365', 'qybazome@mailinator.com', 'Animi fugiat mollit', 'Soluta ea asperiores', 'Rerum aliquid aute b', 'Volunteer Member', 'Active', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-04 07:49:43'),
(13, '', 'Ira Lindsey', 'Nasim Wheeler', 'Henry Zamora', '1989-01-08', 'Male', 'NID', '495', 'Voluptas reprehender', '01785575345', 'wanor@mailinator.com', 'Atque est deserunt v', 'Cum ea in dolores in', 'Explicabo Deleniti', 'General Member', 'Pending', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-04 09:56:56'),
(15, '', 'Duncan Moody', 'Noble Bernard', 'Destiny Whitehead', '2011-11-03', 'Female', 'Passport', '841', 'Voluptas hic totam i', '01787678767', 'noxek@mailinator.com', 'Ea enim et placeat', 'Doloribus dolor mole', 'Voluptates autem eos', 'Volunteer Member', 'Active', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-04 12:00:17'),
(16, '', 'Stone Mitchell', 'Germaine Dickerson', 'Erasmus Wright', '1994-10-24', 'Other', 'Passport', '865', 'Recusandae Voluptat', '01786787656', 'byhy@mailinator.com', 'Voluptas et voluptas', 'In odio obcaecati ac', 'Officiis suscipit en', 'Life Member', 'Pending', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-07 09:53:35'),
(17, '', 'Rhea Hutchinson', 'Colt Riggs', 'Molly Allen', '1988-10-18', 'Female', 'NID', '327', 'Nam aut ad veritatis', '01789786754', 'zexutesu@mailinator.com', 'Quod sed nostrud quo', 'Aut voluptatibus dol', 'Ullamco minim rerum', 'General Member', 'Pending', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-07 09:57:48'),
(18, '', 'Alisa Hawkins', 'Naomi Webb', 'Hope Bass', '1977-05-13', 'Female', 'Passport', '630', 'Nesciunt quis sunt', '01786787644', 'suvigygexy@mailinator.com', 'Nihil doloremque exp', 'Sapiente sint harum', 'Nihil quia libero ex', 'Life Member', 'Pending', '827ccb0eea8a706c4c34a16891f84e7b', '2026-10-07 10:18:35');

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_cta_settings`
--

CREATE TABLE `volunteer_cta_settings` (
  `id` int(11) NOT NULL,
  `banner_title` varchar(255) NOT NULL,
  `banner_description` text NOT NULL,
  `button_text` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT 'Published',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `volunteer_cta_settings`
--

INSERT INTO `volunteer_cta_settings` (`id`, `banner_title`, `banner_description`, `button_text`, `status`, `updated_at`) VALUES
(1, 'আপনিও হতে পারেন আমাদের একজন গর্বিত ভলান্টিয়ার', 'আপনার মেধা, সময় ও শ্রম দিয়ে মানবতার সেবায় অবদান রাখুন। দেশব্যাপী আমাদের বিভিন্ন সামাজিক ও ধর্মীয় উদ্যোগে স্বেচ্ছাসেবক হিসেবে কাজ করতে আজই নিবন্ধ করুন।', 'ভলান্টিয়ার হিসেবে যোগ দিন', 'Published', '2026-09-02 11:29:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_vision`
--
ALTER TABLE `about_vision`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branding_settings`
--
ALTER TABLE `branding_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_event` (`event_name`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_settings`
--
ALTER TABLE `contact_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donation_sectors`
--
ALTER TABLE `donation_sectors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_start_date` (`start_date`);

--
-- Indexes for table `event_volunteers`
--
ALTER TABLE `event_volunteers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_event_user` (`event_id`,`user_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`expense_date`),
  ADD KEY `idx_activity` (`activity_id`),
  ADD KEY `idx_recipient` (`recipient_id`);

--
-- Indexes for table `galleries`
--
ALTER TABLE `galleries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_settings`
--
ALTER TABLE `hero_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cat` (`category_id`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item` (`item_id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`meeting_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `meeting_editors`
--
ALTER TABLE `meeting_editors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user` (`user_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_date` (`status`,`publish_date`),
  ADD KEY `idx_pinned` (`is_pinned`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `notice_editors`
--
ALTER TABLE `notice_editors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user` (`user_id`);

--
-- Indexes for table `portal_settings`
--
ALTER TABLE `portal_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `service_recipients`
--
ALTER TABLE `service_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`member_name`),
  ADD KEY `idx_mobile` (`mobile_no`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due` (`due_date`),
  ADD KEY `idx_project` (`project_id`);

--
-- Indexes for table `task_assignees`
--
ALTER TABLE `task_assignees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_task_user` (`task_id`,`user_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `task_updates`
--
ALTER TABLE `task_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task` (`task_id`);

--
-- Indexes for table `terms_conditions`
--
ALTER TABLE `terms_conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_order` (`status`,`sort_order`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mobile_no` (`mobile_no`);

--
-- Indexes for table `volunteer_cta_settings`
--
ALTER TABLE `volunteer_cta_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_vision`
--
ALTER TABLE `about_vision`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_settings`
--
ALTER TABLE `contact_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `donation_sectors`
--
ALTER TABLE `donation_sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_volunteers`
--
ALTER TABLE `event_volunteers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `galleries`
--
ALTER TABLE `galleries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hero_settings`
--
ALTER TABLE `hero_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `meeting_editors`
--
ALTER TABLE `meeting_editors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notice_editors`
--
ALTER TABLE `notice_editors`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `service_recipients`
--
ALTER TABLE `service_recipients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `task_assignees`
--
ALTER TABLE `task_assignees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `task_updates`
--
ALTER TABLE `task_updates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `terms_conditions`
--
ALTER TABLE `terms_conditions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `volunteer_cta_settings`
--
ALTER TABLE `volunteer_cta_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `event_volunteers`
--
ALTER TABLE `event_volunteers`
  ADD CONSTRAINT `fk_ev_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
