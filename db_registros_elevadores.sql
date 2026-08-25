-- MariaDB dump 10.19  Distrib 10.6.12-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: db_registros_elevadores
-- ------------------------------------------------------
-- Server version	10.6.12-MariaDB-0ubuntu0.22.04.1

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

CREATE DATABASE db_registros_elevadores;
use db_registros_elevadores;

--
-- Table structure for table `reporte`
--

DROP TABLE IF EXISTS `reporte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reporte` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `state_reporte` varchar(10) NOT NULL,
  `title_reporte` varchar(255) NOT NULL,
  `cliente_reporte` varchar(255) DEFAULT NULL,
  `fecha_reporte` varchar(20) DEFAULT NULL,
  `equipo_reporte` varchar(255) DEFAULT NULL,
  `tecnico_reporte` varchar(255) DEFAULT NULL,
  `data_reporte` text DEFAULT NULL,
  `obs_reporte` text DEFAULT NULL,
  `created_at` varchar(20) DEFAULT NULL,
  `updated_at` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reporte`
--

LOCK TABLES `reporte` WRITE;
/*!40000 ALTER TABLE `reporte` DISABLE KEYS */;
INSERT INTO `reporte` VALUES (4,'active','Reporte de reparación Julito','Cliente 4','2023-09-01','Ascensor numero 4','Tecnico 4','{\"s_0_a\":\"OK\",\"s_0_b\":\"OK\",\"s_0_c\":\"OK\",\"s_1_a\":\"X\",\"s_1_b\":\"X\",\"s_1_c\":\"X\",\"s_1_d\":\"X\",\"s_1_e\":\"R\",\"s_1_f\":\"R\",\"s_1_g\":\"R\",\"s_2_a\":\"OK\",\"s_2_b\":\"OK\",\"s_2_c\":\"OK\",\"s_2_d\":\"OK\",\"s_2_e\":\"R\",\"s_2_f\":\"R\",\"s_3_a\":\"X\",\"s_3_b\":\"X\",\"s_3_c\":\"X\",\"s_3_d\":\"X\",\"s_3_e\":\"OK\",\"s_4_a\":\"OK\",\"s_4_b\":\"OK\",\"s_4_c\":\"OK\",\"s_4_d\":\"OK\",\"s_5_a\":\"R\",\"s_5_b\":\"R\",\"s_5_c\":\"R\",\"s_5_d\":\"R\",\"s_5_e\":\"X\",\"s_5_f\":\"X\",\"s_5_g\":\"X\",\"s_5_h\":\"X\"}','{\"ob_1\":\"Este elevador present&oacute; problemas de 220v. Por es el t&eacute;cnico decidi&oacute; a&ntilde;adir una nueva pieza.\",\"ob_2\":\"Este elevador present&oacute; problemas con la maquina de freno\",\"ob_3\":\"Este elevador present&oacute; problemas con el gobernado del cable\",\"ob_4\":\"Este elevador present&oacute; problemas con los terminales de cable\",\"ob_5\":\"Este elevador present&oacute; problemas con la cabina\"}','2023-09-24 08:25:13',NULL);
/*!40000 ALTER TABLE `reporte` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-09-25  5:46:11
