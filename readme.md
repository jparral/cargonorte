<!--
README.md — CargoNorte
Autor/Maintainer: @jparral
Contexto: Proyecto de consultoría (RedPanda)
-->

<div align="center">

# CargoNorte

**Aplicación web en PHP + MySQL + Bootstrap**  
Proyecto de consultoría desarrollado para **RedPanda** (consultora de negocios 360°).

<!-- Badges (dinámicos) -->
<p>
  <img alt="PHP" src="https://img.shields.io/badge/PHP-Backend-777?logo=php">
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-Database-777?logo=mysql">
  <img alt="Bootstrap" src="https://img.shields.io/badge/Bootstrap-UI-777?logo=bootstrap">
</p>

<p>
  <img alt="Repo size" src="https://img.shields.io/github/repo-size/jparral/cargonorte">
  <img alt="Last commit" src="https://img.shields.io/github/last-commit/jparral/cargonorte">
  <img alt="Issues" src="https://img.shields.io/github/issues/jparral/cargonorte">
  <img alt="Top language" src="https://img.shields.io/github/languages/top/jparral/cargonorte">
</p>

<!-- Opcional: badge de visitas (servicio externo). Si no te gusta, borrarlo. -->
<!-- <img alt="Visitors" src="https://visitor-badge.laobi.icu/badge?page_id=jparral.cargonorte"> -->

</div>

---

## Índice
- [Descripción](#descripción)
- [Stack](#stack)
- [Características](#características)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Requisitos](#requisitos)
- [Instalación local](#instalación-local)
- [Base de datos](#base-de-datos)
- [Configuración](#configuración)
- [Ejecución](#ejecución)
- [Deploy en cPanel](#deploy-en-cpanel)
- [Convenciones](#convenciones)
- [Roadmap](#roadmap)
- [Soporte / Contacto](#soporte--contacto)
- [Licencia](#licencia)

---

## Descripción
**CargoNorte** es una aplicación web construida con PHP y MySQL, con UI basada en Bootstrap.  
Incluye un front principal (`index.php`) y una vista asociada a **mapa de rutas** (`mapa_rutas.php`), además de endpoints organizados por carpeta (AJAX/API) y scripts SQL.

> Nota: Este repositorio es parte de una **consultoría para RedPanda** (consultora 360° de estrategia, tecnología e innovación).  
> Sitio: https://redpanda.com.ar/

---

## Stack
- **Backend:** PHP
- **DB:** MySQL / MariaDB
- **Frontend:** Bootstrap + assets estáticos
- **Integraciones:** Endpoints en `api/` y `ajax/`

---

## Características
- UI web con Bootstrap
- Endpoints **AJAX** para operaciones desde el frontend
- Endpoints **API** para integraciones/consumo externo
- Scripts SQL versionados para la base de datos
- Vista `mapa_rutas.php` para funcionalidades de rutas/mapa (según configuración del proyecto)

---

## Estructura del proyecto
Estructura base (carpetas y archivos principales):

├─ ajax/ # Endpoints para requests desde el frontend (XHR/fetch)
├─ api/ # Endpoints tipo API (JSON / integración)
├─ assets/ # CSS/JS/imagenes
├─ config/ # Configuración (DB, constantes, etc.)
├─ includes/ # Helpers, funciones, includes compartidos
├─ sql/ # Scripts de base de datos
├─ index.php # Entry point principal
└─ mapa_rutas.php # Vista / módulo de mapa de rutas
