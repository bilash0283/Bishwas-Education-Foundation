-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 04, 2026 at 07:50 AM
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
  `payment_status` enum('Pending','Paid','Failed') DEFAULT 'Pending',
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
(4, NULL, 'Public', 'Abel Hoffman', 'riwemive@mailinator.com', '+1 (469) 619-2641', 51, 'General', 'General Fund', 'Nagad', 'Aspernatur velit com', 'Pending', '2026-09-12', NULL, '1789215427_2331.png', '2026-09-12 12:17:07', '2026-09-12 12:17:07'),
(6, 82, '', 'Drake Kline', 'binimurop@mailinator.com', '+1 (714) 299-9814', 26, 'Rerum eiusmod ut sit', 'Omnis ea esse conse', 'Dolore esse eum mini', 'Est eveniet quia ut', 'Pending', '2005-11-01', 'Vero occaecat fugit', 'Nostrud dolore verit', '2026-10-01 11:59:50', '2026-10-03 11:36:52'),
(7, NULL, 'Public', 'Porter Jensen', 'qywin@mailinator.com', '+1 (822) 309-4316', 5000, 'General', 'General Fund', 'Nagad', 'Eaque sit natus qui', 'Pending', '2026-10-03', NULL, '1791010912_5108.png', '2026-10-03 07:01:52', '2026-10-03 07:01:52'),
(8, 2, 'Member', 'Unity Buchanan', 'picyvyzo@mailinator.com', '01786588675', 23434, 'Monthly', 'General Fund', 'Rocket', '', 'Paid', '2026-10-03', 'sfsfsf', '', '2026-10-03 11:14:36', '2026-10-03 11:14:36'),
(9, 1, 'Member', 'Kenneth Andrews', 'admin@gmail.com', '01709897865', 444, 'Zakat', 'General Fund', 'Nagod', '5353535', 'Pending', '2026-10-03', 'etetete', '', '2026-10-03 11:15:43', '2026-10-03 11:15:43'),
(11, 2, 'Member', 'Unity Buchanan', 'picyvyzo@mailinator.com', '01786588675', 80, 'General', 'General Fund', 'Cash', 'Itaque occaecat nisi', 'Paid', '1980-05-24', 'Fuga Provident ani', '', '2026-10-03 11:20:32', '2026-10-03 11:20:32'),
(12, 3, 'Member', 'Christen Davenport', 'tafu@mailinator.com', '01689786567', 66, 'General', 'General Fund', 'SureCash', 'Id nemo unde ullamco', 'Pending', '2026-10-04', 'Aut cupidatat cupida', '', '2026-10-03 11:20:50', '2026-10-04 05:45:58'),
(13, 3, 'Member', 'Christen Davenport', 'tafu@mailinator.com', '01689786567', 91, 'Zakat', 'General Fund', 'Nagad', 'ggggggggggggggggg', 'Failed', '2015-01-03', 'Alias modi suscipit', '1791026947_9584.png', '2026-10-03 11:29:07', '2026-10-03 11:29:07'),
(16, 3, 'Member', 'Christen Davenport', 'tafu@mailinator.com', '01689786567', 75, 'Monthly', 'সাধারণ তহবিল', 'Bank', 'Optio modi labore m', 'Pending', '1979-09-27', 'Tenetur ut enim enim', '', '2026-10-03 11:53:21', '2026-10-04 05:21:20');

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
  `membership_status` varchar(50) NOT NULL DEFAULT '',
  `user_type` varchar(30) NOT NULL DEFAULT 'Member',
  `status` enum('Active','Pending') NOT NULL DEFAULT 'Pending',
  `password` varchar(32) NOT NULL DEFAULT '827ccb0eea8a706c4c34a16891f84e7b',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `photo`, `member_name`, `mother_name`, `father_husband_name`, `dob`, `gender`, `id_type`, `id_number`, `qualification`, `mobile_no`, `email`, `present_address`, `permanent_address`, `other_info`, `membership_status`, `user_type`, `status`, `password`, `created_at`) VALUES
(1, '', 'Kenneth Andrews', 'Garrison Snyder', 'Sage Hutchinson', '1999-02-02', 'Other', 'NID', '97', 'Est commodo non qua', '01709897865', 'admin@gmail.com', 'Nulla sit amet elig', 'Exercitation et Nam', 'Adipisci non atque e', 'Admin', 'Admin', 'Active', '827ccb0eea8a706c4c34a16891f84e7b', '2026-09-30 07:17:19'),
(2, '', 'Unity Buchanan', 'Vaughan Baldwin', 'Basil Church', '2012-04-08', 'Male', 'Passport', '210', 'Ex labore quo itaque', '01786588675', 'picyvyzo@mailinator.com', 'Eum do ullam suscipi', 'Quia ut quam eaque N', 'Qui deleniti dolorem', 'Associate Member', 'Member', 'Pending', '827ccb0eea8a706c4c34a16891f84e7b', '2026-09-30 12:07:11'),
(3, '', 'Christen Davenport', 'Lunea Stewart', 'Kiona Moran', '2013-09-27', 'Male', 'Birth Certificate', '394', 'Dolor accusantium su', '01689786567', 'tafu@mailinator.com', 'Praesentium doloremq', 'Aut qui dicta dolore', 'Quia qui dolorem ex', 'Volunteer Member', 'Member', 'Pending', '827ccb0eea8a706c4c34a16891f84e7b', '2026-09-30 12:09:00');

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
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `donation_sectors`
--
ALTER TABLE `donation_sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `volunteer_cta_settings`
--
ALTER TABLE `volunteer_cta_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
