-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: strasa
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('strasa-conteudo-cache-project:1:columns','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:6:{i:0;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:1;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:10:\"Documentos\";s:3:\"key\";s:9:\"documents\";s:5:\"color\";s:7:\"#64748b\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:1;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:10:\"Documentos\";s:3:\"key\";s:9:\"documents\";s:5:\"color\";s:7:\"#64748b\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:3;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:7:\"A Fazer\";s:3:\"key\";s:4:\"todo\";s:5:\"color\";s:7:\"#3b82f6\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:3;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:7:\"A Fazer\";s:3:\"key\";s:4:\"todo\";s:5:\"color\";s:7:\"#3b82f6\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:5;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:12:\"Em Andamento\";s:3:\"key\";s:11:\"in_progress\";s:5:\"color\";s:7:\"#eab308\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:5;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:12:\"Em Andamento\";s:3:\"key\";s:11:\"in_progress\";s:5:\"color\";s:7:\"#eab308\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:7;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:20:\"Fila de Publicação\";s:3:\"key\";s:5:\"queue\";s:5:\"color\";s:7:\"#a855f7\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:7;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:20:\"Fila de Publicação\";s:3:\"key\";s:5:\"queue\";s:5:\"color\";s:7:\"#a855f7\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:4;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:9;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:7:\"Postado\";s:3:\"key\";s:6:\"posted\";s:5:\"color\";s:7:\"#22c55e\";s:8:\"position\";i:0;s:15:\"marks_published\";i:1;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:9;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:7:\"Postado\";s:3:\"key\";s:6:\"posted\";s:5:\"color\";s:7:\"#22c55e\";s:8:\"position\";i:0;s:15:\"marks_published\";i:1;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:5;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:11;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:9:\"Rejeitado\";s:3:\"key\";s:8:\"rejected\";s:5:\"color\";s:7:\"#ef4444\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:1;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:11;s:10:\"company_id\";i:1;s:10:\"project_id\";i:1;s:4:\"name\";s:9:\"Rejeitado\";s:3:\"key\";s:8:\"rejected\";s:5:\"color\";s:7:\"#ef4444\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:1;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1782415638),('strasa-conteudo-cache-project:2:columns','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:6:{i:0;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:2;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:10:\"Documentos\";s:3:\"key\";s:9:\"documents\";s:5:\"color\";s:7:\"#64748b\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:2;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:10:\"Documentos\";s:3:\"key\";s:9:\"documents\";s:5:\"color\";s:7:\"#64748b\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:4;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:7:\"A Fazer\";s:3:\"key\";s:4:\"todo\";s:5:\"color\";s:7:\"#3b82f6\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:4;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:7:\"A Fazer\";s:3:\"key\";s:4:\"todo\";s:5:\"color\";s:7:\"#3b82f6\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:6;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:12:\"Em Andamento\";s:3:\"key\";s:11:\"in_progress\";s:5:\"color\";s:7:\"#eab308\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:6;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:12:\"Em Andamento\";s:3:\"key\";s:11:\"in_progress\";s:5:\"color\";s:7:\"#eab308\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:8;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:20:\"Fila de Publicação\";s:3:\"key\";s:5:\"queue\";s:5:\"color\";s:7:\"#a855f7\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:8;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:20:\"Fila de Publicação\";s:3:\"key\";s:5:\"queue\";s:5:\"color\";s:7:\"#a855f7\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:4;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:10;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:7:\"Postado\";s:3:\"key\";s:6:\"posted\";s:5:\"color\";s:7:\"#22c55e\";s:8:\"position\";i:0;s:15:\"marks_published\";i:1;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:10;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:7:\"Postado\";s:3:\"key\";s:6:\"posted\";s:5:\"color\";s:7:\"#22c55e\";s:8:\"position\";i:0;s:15:\"marks_published\";i:1;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:5;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:12;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:9:\"Rejeitado\";s:3:\"key\";s:8:\"rejected\";s:5:\"color\";s:7:\"#ef4444\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:1;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:12;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";s:9:\"Rejeitado\";s:3:\"key\";s:8:\"rejected\";s:5:\"color\";s:7:\"#ef4444\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:1;s:10:\"created_at\";s:19:\"2026-06-25 16:50:25\";s:10:\"updated_at\";s:19:\"2026-06-25 16:50:25\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1782413025),('strasa-conteudo-cache-project:3:columns','O:39:\"Illuminate\\Database\\Eloquent\\Collection\":2:{s:8:\"\0*\0items\";a:6:{i:0;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:13;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:10:\"Documentos\";s:3:\"key\";s:9:\"documents\";s:5:\"color\";s:7:\"#64748b\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:13;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:10:\"Documentos\";s:3:\"key\";s:9:\"documents\";s:5:\"color\";s:7:\"#64748b\";s:8:\"position\";i:0;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:1;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:14;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:7:\"A Fazer\";s:3:\"key\";s:4:\"todo\";s:5:\"color\";s:7:\"#3b82f6\";s:8:\"position\";i:1;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:14;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:7:\"A Fazer\";s:3:\"key\";s:4:\"todo\";s:5:\"color\";s:7:\"#3b82f6\";s:8:\"position\";i:1;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:2;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:15;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:12:\"Em Andamento\";s:3:\"key\";s:11:\"in_progress\";s:5:\"color\";s:7:\"#eab308\";s:8:\"position\";i:2;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:15;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:12:\"Em Andamento\";s:3:\"key\";s:11:\"in_progress\";s:5:\"color\";s:7:\"#eab308\";s:8:\"position\";i:2;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:3;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:16;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:20:\"Fila de Publicação\";s:3:\"key\";s:5:\"queue\";s:5:\"color\";s:7:\"#a855f7\";s:8:\"position\";i:3;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:16;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:20:\"Fila de Publicação\";s:3:\"key\";s:5:\"queue\";s:5:\"color\";s:7:\"#a855f7\";s:8:\"position\";i:3;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:4;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:17;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:7:\"Postado\";s:3:\"key\";s:6:\"posted\";s:5:\"color\";s:7:\"#22c55e\";s:8:\"position\";i:4;s:15:\"marks_published\";i:1;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:17;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:7:\"Postado\";s:3:\"key\";s:6:\"posted\";s:5:\"color\";s:7:\"#22c55e\";s:8:\"position\";i:4;s:15:\"marks_published\";i:1;s:25:\"requires_rejection_reason\";i:0;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}i:5;O:17:\"App\\Models\\Column\":33:{s:13:\"\0*\0connection\";s:5:\"mysql\";s:8:\"\0*\0table\";s:7:\"columns\";s:13:\"\0*\0primaryKey\";s:2:\"id\";s:10:\"\0*\0keyType\";s:3:\"int\";s:12:\"incrementing\";b:1;s:7:\"\0*\0with\";a:0:{}s:12:\"\0*\0withCount\";a:0:{}s:19:\"preventsLazyLoading\";b:0;s:10:\"\0*\0perPage\";i:15;s:6:\"exists\";b:1;s:18:\"wasRecentlyCreated\";b:0;s:28:\"\0*\0escapeWhenCastingToString\";b:0;s:13:\"\0*\0attributes\";a:11:{s:2:\"id\";i:18;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:9:\"Rejeitado\";s:3:\"key\";s:8:\"rejected\";s:5:\"color\";s:7:\"#ef4444\";s:8:\"position\";i:5;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:1;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:11:\"\0*\0original\";a:11:{s:2:\"id\";i:18;s:10:\"company_id\";i:1;s:10:\"project_id\";i:3;s:4:\"name\";s:9:\"Rejeitado\";s:3:\"key\";s:8:\"rejected\";s:5:\"color\";s:7:\"#ef4444\";s:8:\"position\";i:5;s:15:\"marks_published\";i:0;s:25:\"requires_rejection_reason\";i:1;s:10:\"created_at\";s:19:\"2026-06-25 16:56:47\";s:10:\"updated_at\";s:19:\"2026-06-25 16:56:47\";}s:10:\"\0*\0changes\";a:0:{}s:11:\"\0*\0previous\";a:0:{}s:8:\"\0*\0casts\";a:3:{s:15:\"marks_published\";s:7:\"boolean\";s:25:\"requires_rejection_reason\";s:7:\"boolean\";s:8:\"position\";s:7:\"integer\";}s:17:\"\0*\0classCastCache\";a:0:{}s:21:\"\0*\0attributeCastCache\";a:0:{}s:13:\"\0*\0dateFormat\";N;s:10:\"\0*\0appends\";a:0:{}s:19:\"\0*\0dispatchesEvents\";a:0:{}s:14:\"\0*\0observables\";a:0:{}s:12:\"\0*\0relations\";a:0:{}s:10:\"\0*\0touches\";a:0:{}s:27:\"\0*\0relationAutoloadCallback\";N;s:26:\"\0*\0relationAutoloadContext\";N;s:10:\"timestamps\";b:1;s:13:\"usesUniqueIds\";b:0;s:9:\"\0*\0hidden\";a:0:{}s:10:\"\0*\0visible\";a:0:{}s:11:\"\0*\0fillable\";a:8:{i:0;s:10:\"company_id\";i:1;s:10:\"project_id\";i:2;s:4:\"name\";i:3;s:3:\"key\";i:4;s:5:\"color\";i:5;s:8:\"position\";i:6;s:15:\"marks_published\";i:7;s:25:\"requires_rejection_reason\";}s:10:\"\0*\0guarded\";a:1:{i:0;s:1:\"*\";}}}s:28:\"\0*\0escapeWhenCastingToString\";b:0;}',1782424543);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `segment` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `logo_disk` varchar(255) DEFAULT NULL,
  `color` varchar(7) DEFAULT NULL,
  `bg_type` varchar(20) NOT NULL DEFAULT 'default',
  `bg_color` varchar(20) DEFAULT NULL,
  `bg_gradient` varchar(500) DEFAULT NULL,
  `social_networks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_networks`)),
  `default_columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_columns`)),
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_company_id_slug_unique` (`company_id`,`slug`),
  KEY `clients_company_id_archived_at_index` (`company_id`,`archived_at`),
  CONSTRAINT `clients_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,1,'Tech Solutions Brasil','tech-solutions','Tecnologia',NULL,NULL,NULL,'[\"instagram\",\"linkedin\",\"blog\"]','[{\"name\":\"Documentos\",\"key\":\"documents\",\"color\":\"#64748b\"},{\"name\":\"A Fazer\",\"key\":\"todo\",\"color\":\"#3b82f6\"},{\"name\":\"Em Andamento\",\"key\":\"in_progress\",\"color\":\"#eab308\"},{\"name\":\"Fila de Publica\\u00e7\\u00e3o\",\"key\":\"queue\",\"color\":\"#a855f7\"},{\"name\":\"Postado\",\"key\":\"posted\",\"color\":\"#22c55e\",\"marks_published\":true},{\"name\":\"Rejeitado\",\"key\":\"rejected\",\"color\":\"#ef4444\",\"requires_rejection_reason\":true}]',NULL,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(3,1,'toca','toca',NULL,'0','s3',NULL,'[\"instagram\"]','[{\"name\":\"Documentos\",\"key\":\"documents\",\"color\":\"#64748b\"},{\"name\":\"A Fazer\",\"key\":\"todo\",\"color\":\"#3b82f6\"},{\"name\":\"Em Andamento\",\"key\":\"in_progress\",\"color\":\"#eab308\"},{\"name\":\"Fila de Publica\\u00e7\\u00e3o\",\"key\":\"queue\",\"color\":\"#a855f7\"},{\"name\":\"Postado\",\"key\":\"posted\",\"color\":\"#22c55e\",\"marks_published\":true},{\"name\":\"Rejeitado\",\"key\":\"rejected\",\"color\":\"#ef4444\",\"requires_rejection_reason\":true}]',NULL,'2026-06-25 19:56:25','2026-06-25 19:56:25'),(4,1,'STR','str',NULL,'company-1/logos/pXDHsVS99oqdCWBmKfyqFLUMAlBkPPTq09eYMZWR.png','s3','#0105f4',NULL,'[{\"name\":\"Documentos\",\"key\":\"documents\",\"color\":\"#64748b\"},{\"name\":\"A Fazer\",\"key\":\"todo\",\"color\":\"#3b82f6\"},{\"name\":\"Em Andamento\",\"key\":\"in_progress\",\"color\":\"#eab308\"},{\"name\":\"Fila de Publica\\u00e7\\u00e3o\",\"key\":\"queue\",\"color\":\"#a855f7\"},{\"name\":\"Postado\",\"key\":\"posted\",\"color\":\"#22c55e\",\"marks_published\":true},{\"name\":\"Rejeitado\",\"key\":\"rejected\",\"color\":\"#ef4444\",\"requires_rejection_reason\":true}]',NULL,'2026-06-25 22:37:23','2026-06-25 22:37:41');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `columns`
--

DROP TABLE IF EXISTS `columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `columns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `key` varchar(255) DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#64748b',
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  `marks_published` tinyint(1) NOT NULL DEFAULT 0,
  `requires_rejection_reason` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `columns_company_id_foreign` (`company_id`),
  KEY `columns_project_id_position_index` (`project_id`,`position`),
  CONSTRAINT `columns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `columns_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `columns`
--

LOCK TABLES `columns` WRITE;
/*!40000 ALTER TABLE `columns` DISABLE KEYS */;
INSERT INTO `columns` VALUES (1,1,1,'Documentos','documents','#64748b',0,0,0,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(3,1,1,'A Fazer','todo','#3b82f6',0,0,0,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(5,1,1,'Em Andamento','in_progress','#eab308',0,0,0,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(7,1,1,'Fila de Publicação','queue','#a855f7',0,0,0,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(9,1,1,'Postado','posted','#22c55e',0,1,0,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(11,1,1,'Rejeitado','rejected','#ef4444',0,0,1,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(13,1,3,'Documentos','documents','#64748b',0,0,0,'2026-06-25 19:56:47','2026-06-25 19:56:47'),(14,1,3,'A Fazer','todo','#3b82f6',1,0,0,'2026-06-25 19:56:47','2026-06-25 19:56:47'),(15,1,3,'Em Andamento','in_progress','#eab308',2,0,0,'2026-06-25 19:56:47','2026-06-25 19:56:47'),(16,1,3,'Fila de Publicação','queue','#a855f7',3,0,0,'2026-06-25 19:56:47','2026-06-25 19:56:47'),(17,1,3,'Postado','posted','#22c55e',4,1,0,'2026-06-25 19:56:47','2026-06-25 19:56:47'),(18,1,3,'Rejeitado','rejected','#ef4444',5,0,1,'2026-06-25 19:56:47','2026-06-25 19:56:47');
/*!40000 ALTER TABLE `columns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Agência de Marketing Strasa','agencia-strasa','2026-06-25 19:50:24','2026-06-25 19:50:24');
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_invitations`
--

DROP TABLE IF EXISTS `company_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `invited_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'member',
  `token` varchar(64) NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_invitations_company_id_email_unique` (`company_id`,`email`),
  UNIQUE KEY `company_invitations_token_unique` (`token`),
  KEY `company_invitations_invited_by_foreign` (`invited_by`),
  CONSTRAINT `company_invitations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_invitations`
--

LOCK TABLES `company_invitations` WRITE;
/*!40000 ALTER TABLE `company_invitations` DISABLE KEYS */;
/*!40000 ALTER TABLE `company_invitations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_01_000001_create_companies_table',1),(5,'2026_06_01_000002_add_company_and_role_to_users_table',1),(6,'2026_06_01_000003_create_company_invitations_table',1),(7,'2026_06_01_000004_create_clients_table',1),(8,'2026_06_01_000005_create_projects_table',1),(9,'2026_06_01_000006_create_columns_table',1),(10,'2026_06_01_000007_create_tags_table',1),(11,'2026_06_01_000008_create_tasks_table',1),(12,'2026_06_01_000009_create_task_tag_table',1),(13,'2026_06_01_000010_create_task_comments_table',1),(14,'2026_06_01_000011_create_task_attachments_table',1),(15,'2026_06_01_000012_create_task_activities_table',1),(16,'2026_06_25_183206_create_project_user_favorites_table',2),(17,'2026_06_25_183317_add_status_to_projects_table',2),(18,'2026_06_25_192138_add_avatar_fields_to_users_table',3),(19,'2026_06_25_192235_add_color_to_clients_table',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_user_favorites`
--

DROP TABLE IF EXISTS `project_user_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_user_favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_user_favorites_project_id_user_id_unique` (`project_id`,`user_id`),
  KEY `project_user_favorites_user_id_foreign` (`user_id`),
  CONSTRAINT `project_user_favorites_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_user_favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_user_favorites`
--

LOCK TABLES `project_user_favorites` WRITE;
/*!40000 ALTER TABLE `project_user_favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_user_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_client_id_foreign` (`client_id`),
  KEY `projects_company_id_client_id_index` (`company_id`,`client_id`),
  CONSTRAINT `projects_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,1,1,'Redes Sociais 2026','Planejamento de redes sociais para 2026',NULL,'2026-06-25 19:50:25','2026-06-25 19:50:25',NULL),(3,1,3,'teste','teste 2',NULL,'2026-06-25 19:56:47','2026-06-26 00:40:14','on_track');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('eoUZHHxctWXeRAlQjvyKiNDXMBsrV5j1Xvxt5HTq',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiSXlha1FLNVA0WlNqNFdWUHRqSHRkQ04yeUNYZVpPMlV3aUdyMDR0TSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjM4OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvcHJvamVjdHMvMy9ib2FyZCI7czo1OiJyb3V0ZSI7czoxNDoicHJvamVjdHMuYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1782423943);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#94a3b8',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_company_id_name_unique` (`company_id`,`name`),
  CONSTRAINT `tags_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,1,'corno','#22c55e','2026-06-26 00:40:23','2026-06-26 00:40:23');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_activities`
--

DROP TABLE IF EXISTS `task_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_activities_company_id_foreign` (`company_id`),
  KEY `task_activities_user_id_foreign` (`user_id`),
  KEY `task_activities_task_id_created_at_index` (`task_id`,`created_at`),
  CONSTRAINT `task_activities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_activities_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_activities`
--

LOCK TABLES `task_activities` WRITE;
/*!40000 ALTER TABLE `task_activities` DISABLE KEYS */;
INSERT INTO `task_activities` VALUES (1,1,4,1,'created','criou a tarefa',NULL,'2026-06-25 20:06:53','2026-06-25 20:06:53'),(2,1,4,1,'assignee_changed','alterou o responsável',NULL,'2026-06-25 20:08:07','2026-06-25 20:08:07'),(3,1,4,1,'column_changed','moveu de \"A Fazer\" para \"Documentos\"',NULL,'2026-06-25 21:17:53','2026-06-25 21:17:53'),(4,1,4,1,'column_changed','moveu de \"Documentos\" para \"Postado\"',NULL,'2026-06-25 21:20:56','2026-06-25 21:20:56'),(5,1,4,1,'published','marcou como publicado',NULL,'2026-06-25 21:20:56','2026-06-25 21:20:56'),(6,1,4,1,'column_changed','moveu de \"Postado\" para \"Documentos\"',NULL,'2026-06-25 21:21:09','2026-06-25 21:21:09'),(7,1,5,1,'created','criou a tarefa',NULL,'2026-06-25 21:29:27','2026-06-25 21:29:27'),(8,1,4,1,'column_changed','moveu de \"Documentos\" para \"A Fazer\"',NULL,'2026-06-25 22:00:03','2026-06-25 22:00:03'),(9,1,4,1,'column_changed','moveu de \"A Fazer\" para \"Documentos\"',NULL,'2026-06-25 22:00:09','2026-06-25 22:00:09'),(10,1,4,1,'column_changed','moveu de \"Documentos\" para \"Postado\"',NULL,'2026-06-25 22:00:24','2026-06-25 22:00:24'),(11,1,4,1,'published','marcou como publicado',NULL,'2026-06-25 22:00:24','2026-06-25 22:00:24'),(12,1,4,1,'column_changed','moveu de \"Postado\" para \"Em Andamento\"',NULL,'2026-06-25 22:19:35','2026-06-25 22:19:35'),(13,1,4,1,'column_changed','moveu de \"Em Andamento\" para \"Postado\"',NULL,'2026-06-25 22:19:45','2026-06-25 22:19:45'),(14,1,4,1,'published','marcou como publicado',NULL,'2026-06-25 22:19:45','2026-06-25 22:19:45'),(15,1,4,1,'column_changed','moveu de \"Postado\" para \"Fila de Publicação\"',NULL,'2026-06-25 22:19:51','2026-06-25 22:19:51'),(16,1,4,1,'column_changed','moveu de \"Fila de Publicação\" para \"Em Andamento\"',NULL,'2026-06-25 22:19:56','2026-06-25 22:19:56'),(17,1,4,1,'column_changed','moveu de \"Em Andamento\" para \"Postado\"',NULL,'2026-06-25 22:20:03','2026-06-25 22:20:03'),(18,1,4,1,'published','marcou como publicado',NULL,'2026-06-25 22:20:03','2026-06-25 22:20:03'),(19,1,4,1,'column_changed','moveu de \"Postado\" para \"A Fazer\"',NULL,'2026-06-25 22:36:35','2026-06-25 22:36:35'),(20,1,4,1,'column_changed','moveu de \"A Fazer\" para \"Fila de Publicação\"',NULL,'2026-06-25 22:36:46','2026-06-25 22:36:46'),(21,1,4,1,'column_changed','moveu de \"Fila de Publicação\" para \"Em Andamento\"',NULL,'2026-06-25 22:36:53','2026-06-25 22:36:53'),(22,1,4,1,'column_changed','moveu de \"Em Andamento\" para \"A Fazer\"',NULL,'2026-06-25 22:39:23','2026-06-25 22:39:23'),(23,1,6,1,'created','criou a tarefa',NULL,'2026-06-25 22:41:40','2026-06-25 22:41:40'),(24,1,4,1,'column_changed','moveu de \"A Fazer\" para \"Em Andamento\"',NULL,'2026-06-25 22:43:25','2026-06-25 22:43:25'),(25,1,4,1,'column_changed','moveu de \"Em Andamento\" para \"A Fazer\"',NULL,'2026-06-25 22:43:39','2026-06-25 22:43:39'),(26,1,4,1,'column_changed','moveu de \"A Fazer\" para \"Fila de Publicação\"',NULL,'2026-06-25 22:43:45','2026-06-25 22:43:45'),(27,1,4,1,'column_changed','moveu de \"Fila de Publicação\" para \"A Fazer\"',NULL,'2026-06-25 22:44:10','2026-06-25 22:44:10'),(28,1,4,1,'column_changed','moveu de \"A Fazer\" para \"Em Andamento\"',NULL,'2026-06-25 22:57:48','2026-06-25 22:57:48'),(29,1,4,1,'column_changed','moveu de \"Em Andamento\" para \"A Fazer\"',NULL,'2026-06-25 22:57:54','2026-06-25 22:57:54'),(30,1,4,1,'column_changed','moveu de \"A Fazer\" para \"Em Andamento\"',NULL,'2026-06-25 22:57:59','2026-06-25 22:57:59'),(31,1,6,1,'column_changed','moveu de \"Em Andamento\" para \"Documentos\"',NULL,'2026-06-25 22:58:06','2026-06-25 22:58:06'),(32,1,4,1,'column_changed','moveu de \"Em Andamento\" para \"A Fazer\"',NULL,'2026-06-25 22:58:16','2026-06-25 22:58:16'),(33,1,4,1,'column_changed','moveu de \"A Fazer\" para \"Em Andamento\"',NULL,'2026-06-25 22:58:33','2026-06-25 22:58:33'),(34,1,6,1,'column_changed','moveu de \"Documentos\" para \"A Fazer\"',NULL,'2026-06-25 22:59:12','2026-06-25 22:59:12'),(35,1,4,1,'column_changed','moveu de \"Em Andamento\" para \"A Fazer\"',NULL,'2026-06-26 00:02:41','2026-06-26 00:02:41'),(36,1,4,1,'assignee_changed','alterou o responsável',NULL,'2026-06-26 00:32:15','2026-06-26 00:32:15');
/*!40000 ALTER TABLE `task_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_attachments`
--

DROP TABLE IF EXISTS `task_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 's3',
  `path` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `is_image` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_attachments_company_id_foreign` (`company_id`),
  KEY `task_attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `task_attachments_task_id_index` (`task_id`),
  CONSTRAINT `task_attachments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_attachments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_attachments`
--

LOCK TABLES `task_attachments` WRITE;
/*!40000 ALTER TABLE `task_attachments` DISABLE KEYS */;
INSERT INTO `task_attachments` VALUES (1,1,4,1,'s3','company-1/tasks/4/f9c0c1ba-aa54-41f8-a7f4-94227f3517d1.png','toca.png','image/png',253331,1,'2026-06-25 20:33:01','2026-06-25 20:33:01'),(2,1,4,1,'s3','company-1/tasks/4/6aeec7f4-c55b-4105-a9b4-e5520fa8f161.png','Screenshot_1-removebg-preview.png','image/png',89303,1,'2026-06-25 20:56:40','2026-06-25 20:56:40'),(3,1,4,1,'s3','company-1/tasks/4/8736e2c1-c392-4299-b5ea-f5f19e1d09f4.png','jornada.png','image/png',3158447,1,'2026-06-25 20:57:02','2026-06-25 20:57:02');
/*!40000 ALTER TABLE `task_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_comments`
--

DROP TABLE IF EXISTS `task_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `mentions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mentions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_comments_company_id_foreign` (`company_id`),
  KEY `task_comments_user_id_foreign` (`user_id`),
  KEY `task_comments_task_id_created_at_index` (`task_id`,`created_at`),
  CONSTRAINT `task_comments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_comments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_comments`
--

LOCK TABLES `task_comments` WRITE;
/*!40000 ALTER TABLE `task_comments` DISABLE KEYS */;
INSERT INTO `task_comments` VALUES (1,1,4,1,'dasdasd','[]','2026-06-25 21:22:09','2026-06-25 21:22:09');
/*!40000 ALTER TABLE `task_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_tag`
--

DROP TABLE IF EXISTS `task_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_tag` (
  `task_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`task_id`,`tag_id`),
  KEY `task_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `task_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_tag_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_tag`
--

LOCK TABLES `task_tag` WRITE;
/*!40000 ALTER TABLE `task_tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `column_id` bigint(20) unsigned NOT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `content_type` varchar(255) DEFAULT NULL,
  `social_networks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_networks`)),
  `publish_date` date DEFAULT NULL,
  `position` int(10) unsigned NOT NULL DEFAULT 0,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_column_id_foreign` (`column_id`),
  KEY `tasks_assignee_id_foreign` (`assignee_id`),
  KEY `tasks_created_by_foreign` (`created_by`),
  KEY `tasks_project_id_column_id_position_index` (`project_id`,`column_id`,`position`),
  KEY `tasks_client_id_publish_date_index` (`client_id`,`publish_date`),
  KEY `tasks_company_id_assignee_id_index` (`company_id`,`assignee_id`),
  CONSTRAINT `tasks_assignee_id_foreign` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_column_id_foreign` FOREIGN KEY (`column_id`) REFERENCES `columns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,1,1,1,3,2,NULL,'Post: Nova IA no mercado','Criar carrossel com 5 dicas sobre como usar IA','carrossel','[\"instagram\",\"linkedin\"]','2026-06-27',0,0,NULL,NULL,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(2,1,1,1,5,1,NULL,'Blog: O que esperar de 2026','Texto de 1000 palavras para o blog','blog','[\"blog\"]','2026-06-30',0,0,NULL,NULL,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(3,1,1,1,9,2,NULL,'Reels: Lançamento do Produto','Vídeo de 30s','reel','[\"instagram\"]','2026-06-24',0,1,NULL,NULL,'2026-06-25 19:50:25','2026-06-25 19:50:25'),(4,1,3,3,14,1,1,'post cabelo jao','meu pau na sua mao',NULL,'[\"instagram\"]','2026-06-26',1,0,NULL,NULL,'2026-06-25 20:06:53','2026-06-26 00:32:15'),(5,1,3,3,13,NULL,1,'Nova Tarefa',NULL,NULL,'[]',NULL,0,0,NULL,NULL,'2026-06-25 21:29:27','2026-06-25 22:58:06'),(6,1,3,3,14,NULL,1,'Nova Tarefa',NULL,NULL,'[]',NULL,0,0,NULL,NULL,'2026-06-25 22:41:40','2026-06-26 00:02:41');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'member',
  `avatar_color` varchar(7) NOT NULL DEFAULT '#6366f1',
  `avatar_path` varchar(255) DEFAULT NULL,
  `avatar_disk` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_id_foreign` (`company_id`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Felipe Ruiz','admin@strasa.com','admin','#6366f1','company-1/avatars/e3e0fbb7-2573-49b7-b1c0-857dff496e0f.jpg','s3',NULL,'$2y$12$6DU/FSH/pCW8Y3LbmwUbLuFwRfBJEGAvIqRE8XaopYND1EuULXo7K','FIwupSRRt2n8ji5cS0PM1G9WeAI5mV4q4K2syeFwsRZzKi7f59Ok32LCe1RR','2026-06-25 19:50:25','2026-06-25 22:42:46'),(2,1,'João Designer','joao@strasa.com','colaborador','#6366f1',NULL,NULL,NULL,'$2y$12$.6hCf0aMJpoMaZSKPUugEeig3fvFxtVaKUh.xbV3Ozhm0f4mJd8aC',NULL,'2026-06-25 19:50:25','2026-06-25 19:50:25');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-25 19:27:43
