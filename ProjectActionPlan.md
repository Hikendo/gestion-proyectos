# ProjectActionPlan.md — Plan de Acción para Refactorización

**Proyecto:** crm-back
**Fecha de elaboración:** 2026-06-16
**Versión:** 1.0
**Fuente principal:** Documentación en `DDocumentacion/` (14 archivos) + análisis complementario del código fuente
**Advertencia:** Este documento es un plan de acción. No contiene implementaciones, código, PRs, ni modificaciones.

---

# Información del Proyecto

## Nombre

crm-back — Sistema de Inversión (CRM + Back Office Financiero)

## Descripción

Sistema back-end para la gestión de fondos de inversión. Administra inversionistas, movimientos (compras/ventas de títulos), contratos, aportaciones, retiros, estados de cuenta, comisiones de IBs, y notificaciones push. Expone una API REST JSON consumida por frontends SPA y aplicaciones móviles. Incluye integraciones con OpenPay (pagos) y Firebase Cloud Messaging (push).

## Objetivo general

Modernizar y estabilizar el sistema CRM-Back de forma incremental, sin interrumpir la operación, para que alcance un estado de madurez técnica que permita: cumplimiento regulatorio (KYC/AML), automatización de flujos financieros, arquitectura mantenible y escalable, y un modelo de autorización granular.

## Objetivos específicos

1. Cerrar brechas regulatorias críticas (KYC/AML, firma electrónica, conciliación bancaria).
2. Automatizar flujos que hoy son manuales (fondeo, scheduler, conciliación).
3. Unificar la arquitectura híbrida en un estándar consistente (Service Layer + Repository Pattern + Policies).
4. Eliminar deuda técnica observable (modelos huérfanos, código duplicado, nomenclatura inconsistente).
5. Establecer una base de documentación técnica viva (ADR, OpenAPI, diagramas).
6. Preparar la arquitectura para futura evolución sin comprometer la estabilidad operativa.

## Estado actual identificado

El sistema está **funcional para operación manual**. Los procesos core existen: crear inversionista → registro → contratos → fondeo → EDCs → retiros. Sin embargo, presenta deuda técnica significativa, vacíos funcionales regulatorios, y una arquitectura híbrida inconsistente. No existe automatización de flujos, no hay Scheduler activo, y la conciliación es enteramente manual.

## Alcance del análisis

- Análisis basado en la documentación existente en `DDocumentacion/` generada el 2026-06-15.
- El código fuente fue consultado de forma complementaria para verificar hallazgos críticos.
- No se realizaron pruebas, no se evaluó calidad de código, no se inspeccionó seguridad.
- **Todo lo consignado en este plan se deriva de evidencia encontrada en la documentación o en el código fuente, diferenciando hechos de inferencias.**

## Restricciones

- No se modifica código.
- No se generan PRs.
- No se proponen implementaciones concretas.
- No se evalúa estilo de código (PSR), cobertura de pruebas, ni seguridad.
- El único entregable permitido es `ProjectActionPlan.md`.
- Las fechas son estimaciones tentativas, no compromisos.

---

# Resumen Ejecutivo

## Estado general

**Sistema en operación con madurez técnica baja-media.** Los módulos core están completos pero operan con intervención manual. Existen 33 módulos identificados: 20 completos, 9 parciales, 2 esperados faltantes (Inversora Foráneo, Trading), y 14 recomendados.

## Nivel de madurez

- **Modelo de madurez estimado:** Nivel 2 (Repeatable) tendiendo a Nivel 1 (Initial) en algunos subsistemas.
- **Arquitectura:** Monolito híbrido con Service Layer parcial. Sin Repository Pattern. Sin Event-Driven Architecture. Sin Policies de Laravel.
- **Documentación:** Recién generada (14 archivos en DDocumentacion/). Previamente inexistente.
- **Automatización:** Mínima. Sin Scheduler activo. Jobs solo para notificaciones FCM.

## Riesgos generales

1. **Riesgo regulatorio alto:** Sin KYC/AML formal, sin firma electrónica vinculante, sin CFDIs fiscales.
2. **Riesgo operativo alto:** Conciliación manual, sin Scheduler, fondeo manual.
3. **Riesgo de mantenibilidad medio:** Arquitectura híbrida, modelos huérfanos, nomenclatura inconsistente.
4. **Riesgo de escalabilidad bajo:** Laravel Octane y Horizon ya están configurados, la base técnica para escalar existe.

## Prioridades

1. **Crítica inmediata:** Cerrar brechas regulatorias (KYC/AML, firma electrónica).
2. **Alta a corto plazo:** Automatizar fondeo/conciliación, activar Scheduler, notificaciones email.
3. **Media a mediano plazo:** Unificar arquitectura, eliminar deuda técnica, implementar Policies.
4. **Baja a largo plazo:** Extender funcionalidad (CRM, dashboards, webhooks salientes, market data).

## Dependencias importantes

- Cualquier refactorización del modelo de autorización (migrar a Policies) depende de no romper el sistema Spatie Permission existente.
- La unificación de controladores (`InversionistaController` vs `InversionistasController`) es prerequisito para estandarizar la arquitectura.
- La limpieza de modelos huérfanos requiere auditoría de dependencias externas (sistemas legacy que compartan la BD).
- La integración bancaria automática depende de disponibilidad de APIs de terceros (STP/SPEI).

---

# Plan General de Refactorización

## Estrategia de alto nivel

Se plantea una **refactorización incremental por fases**, priorizando la estabilidad operativa y el cumplimiento regulatorio sobre la pureza arquitectónica. El principio rector es: **"primero estabilizar y asegurar, luego estandarizar, finalmente extender"**.

### Corto plazo (Fase 0 – Fase 2, 0–1.5 meses)

1. **Auditoría funcional completa y validación con stakeholders.**
2. **Documentación viva:** ADR, OpenAPI, diagramas actualizados.
3. **Cierre de brechas regulatorias urgentes:** KYC/AML básico, firma electrónica.
4. **Automatización de flujos críticos:** Scheduler con tareas mínimas, email transaccional.
5. **Estandarización arquitectónica inicial:** Unificar nomenclatura DTO/DTOS, definir coding standards.

**Justificación:** Lo urgente (regulatorio, operativo) no puede esperar a una refactorización profunda. Se implementan las protecciones mínimas requeridas mientras se planifica la modernización.

### Mediano plazo (Fase 3 – Fase 4, 1.5–3 meses)

1. **Refactorización controlada:** Unificar controladores duplicados, extraer servicios donde falten, implementar Repository Pattern progresivamente.
2. **Migración de autorización:** Implementar Policies de Laravel coexistiendo con Spatie Permission.
3. **Limpieza de deuda técnica:** Eliminar modelos huérfanos, unificar duplicados, renombrar tablas inconsistentes (con migraciones).
4. **Dashboard operativo y reportes.**
5. **Workflow de aprobación multi-nivel.**

**Justificación:** Con el sistema estable y documentado, se puede abordar la deuda técnica sin riesgo operativo. La migración a Policies es progresiva (convivencia, no reemplazo abrupto).

### Largo plazo (Fase 5 – Fase 6, 3–4 meses)

1. **Extensión funcional:** Módulo de Trading (si aplica), CRM/Prospectos, Inversora Foráneo.
2. **Event-Driven Architecture:** Implementar eventos de dominio para desacoplar lógicas.
3. **Integraciones avanzadas:** Conciliación bancaria automática vía STP/SPEI, webhooks salientes, API de Market Data.
4. **Observabilidad y monitoreo:** Métricas de negocio, alertas.
5. **Gestión documental avanzada.**

**Justificación:** Solo después de tener una arquitectura sólida y estandarizada tiene sentido extender funcionalidad. Extender sobre una base inestable multiplica la deuda técnica.

---

# Fases

## Fase 0: Descubrimiento y Alineación

### Objetivo

Validar los hallazgos de la auditoría documental con los stakeholders del negocio. Confirmar supuestos, identificar requerimientos no documentados y alinear expectativas sobre el plan de refactorización.

### Dependencias

- Ninguna. Es la fase inicial.

### Entregables

- Acta de validación de hallazgos (confirmados vs descartados).
- Mapa de stakeholders y responsables funcionales por módulo.
- Lista priorizada de requerimientos de negocio no cubiertos.
- Cronograma tentativo validado con el negocio.
- Identificación de bloqueadores externos (APIs bancarias, proveedores de KYC, firma electrónica).

### Riesgos

- **Falta de disponibilidad de stakeholders:** El conocimiento del negocio puede estar solo en personas clave no disponibles.
- **Requisitos regulatorios no claros:** Si no se define el alcance exacto de KYC/AML requerido, no se puede dimensionar el esfuerzo.
- **Dependencias externas no confirmadas:** APIs de terceros pueden no estar contratadas o disponibles.

### Bloqueadores

- Inexistencia de un Product Owner o responsable funcional designado.
- Desconocimiento de la normativa aplicable (CNBV, CONDUSEF, SAT).

### Criterios de finalización

- [ ] Hallazgos de la auditoría validados con al menos un stakeholder por área (comercial, finanzas, legal, sistemas).
- [ ] Requerimientos regulatorios mínimos documentados y aceptados.
- [ ] Lista de dependencias externas confirmadas (APIs, proveedores).
- [ ] Plan de fases validado y ajustado con base en feedback del negocio.

---

## Fase 1: Documentación Viva y Línea Base

### Objetivo

Establecer una base de documentación técnica viva que sirva como referencia única para el equipo de desarrollo y stakeholders técnicos. Esto incluye ADR, especificación OpenAPI, y diagramas mantenibles.

### Dependencias

- Fase 0 completada (requerimientos validados).
- Acceso a repositorio de documentación (wiki, Notion, Confluence, o carpeta `docs/` en el repo).

### Entregables

- **ADR inicial:** Decisiones de arquitectura documentadas (al menos 5: por qué Sanctum sobre Passport, por qué Spatie Permission sobre Policies nativas, por qué monolith sobre microservicios, por qué cola Redis, por qué DomPDF sobre otra librería).
- **Especificación OpenAPI 3.0:** Documentación interactiva de los 76 endpoints con Schemas, Requests, Responses.
- **Colección Postman actualizada:** Basada en la colección existente en `DDocumentacion/crm-back.postman_collection.json`, enriquecida con variables de entorno y tests básicos.
- **Diagramas Mermaid mantenibles:** Arquitectura, entidades, procesos, módulos (actualizar los generados en DDocumentacion/ como versión inicial).
- **README.md técnico:** Guía de onboarding para desarrolladores (stack, setup, arquitectura, convenciones).

### Riesgos

- **Documentación que se desactualiza:** Sin un proceso de mantenimiento, la documentación pierde valor. Se requiere disciplina de equipo.
- **Especificación OpenAPI manual:** 76 endpoints es un esfuerzo considerable. Evaluar uso de librerías como `scribe` o `scramble` para generación automática.

### Bloqueadores

- Falta de acuerdo en el estándar de documentación (¿dónde vive?, ¿quién la mantiene?).

### Criterios de finalización

- [ ] Al menos 5 ADR documentados y revisados por el equipo técnico.
- [ ] Especificación OpenAPI 3.0 completa para todos los endpoints de la API v1.
- [ ] Colección Postman funcional (todos los endpoints ejecutables desde la colección).
- [ ] Diagramas Mermaid actualizados en el repositorio de documentación.
- [ ] README.md con guía de onboarding completa.

---

## Fase 2: Estabilización Regulatoria y Operativa (Corto Plazo)

### Objetivo

Cerrar las brechas más críticas identificadas sin refactorizar la arquitectura existente. El foco es regulatorio (KYC/AML, firma) y operativo (Scheduler, notificaciones email).

### Dependencias

- Fase 0 completada (requerimientos regulatorios claros).
- Fase 1 iniciada (documentación de endpoints existentes para no romper nada).

### Entregables

1. **Validación KYC/AML básica:**
   - Integración con servicio de validación de identidad (INE, CURP, RFC).
   - Listas negras/AML (integración con proveedor externo).
   - Scoring de riesgo básico.
   - Endpoints de consulta de estatus KYC.
2. **Firma electrónica vinculante:**
   - Reemplazo de la captura PNG por integración con proveedor de e-Firma (DocuSign, SignNow, o FIEL).
   - Aplicación de firma a los 3 tipos de contrato (Adhesión, Invitación, LPOA).
3. **Notificaciones email transaccional:**
   - Emails para: cambio de contraseña, solicitud de retiro creada/aprobada/rechazada, estado de cuenta disponible, bienvenida.
   - Templates HTML responsivos.
   - Queue con Redis para envío asíncrono.
4. **Activación del Scheduler:**
   - Tareas mínimas: limpieza de tokens expirados, envío de recordatorios de EDC, generación de reportes periódicos.
   - Configuración de cron job en servidor.
5. **Roles de negocio adicionales:**
   - Crear los 5 roles nuevos recomendados: Comercial, Finanzas, MesaNegocios, AuxiliarAdministrativo, AnalisisProduccion.

### Riesgos

- **Integración con proveedores externos:** Dependencia de disponibilidad, costos, y tiempos de contratación.
- **Cambios en el flujo de registro:** Agregar KYC puede romper el flujo actual de creación de inversionistas si no se diseña cuidadosamente.
- **Migración de firmas existentes:** Contratos ya firmados con PNG no pueden "re-firmarse" electrónicamente. Definir estrategia de convivencia.

### Bloqueadores

- Contratación de proveedores de KYC y firma electrónica no realizada.
- Requisitos legales no especificados (¿qué nivel de KYC exige la regulación aplicable?).
- Acceso a servidor para configurar cron job.

### Criterios de finalización

- [ ] KYC/AML: todo nuevo inversionista pasa por validación de identidad antes de poder invertir.
- [ ] Firma electrónica: contratos nuevos se firman con e-Firma vinculante.
- [ ] Emails transaccionales: al menos 5 tipos de notificación por email implementados y funcionando.
- [ ] Scheduler: al menos 3 tareas programadas corriendo en producción.
- [ ] Roles nuevos: 5 roles adicionales creados y asignables desde el sistema.

---

## Fase 3: Estandarización Arquitectónica

### Objetivo

Unificar la arquitectura del sistema bajo un estándar consistente: Service Layer para toda lógica de negocio, Repository Pattern para acceso a datos, DTOs unificados, y eliminación de código duplicado.

### Dependencias

- Fase 1 completada (documentación de endpoints existentes para pruebas de regresión).
- Fase 2 completada (estabilidad regulatoria y operativa alcanzada).

### Entregables

1. **Unificación de DTOs:**
   - Eliminar carpeta `app/DTO/` o `app/DTOS/`. Dejar una sola (`app/DTOs/` con `S` final).
   - Mover todos los DTOs a la carpeta unificada.
   - Actualizar todos los imports.
2. **Unificación de controladores duplicados:**
   - Fusionar `InversionistaController` e `InversionistasController` en un solo controlador con Service Layer consistente.
   - Mover toda lógica de negocio de los controladores a Services.
3. **Implementación progresiva de Repository Pattern:**
   - Crear interfaces de repositorio para las entidades core: `Inversionista`, `Movimiento`, `SolicitudRetiro`, `Comision`.
   - Implementar repositorios Eloquent.
   - Inyectar repositorios en Services (no en Controladores directamente).
4. **Eliminación de modelos huérfanos:**
   - Deprecar modelos sin uso: `Compra`, `Ventas`, `CETS`, `Rendimientos`, `AccesoCRM`, `AccesoTemporal`, `IntegracionTemporal`, `DatoInversionista`, `DatosInversionistas`, `RegistroInversionistasTemporales`, `PreImportacion`.
   - Verificar que ninguna tabla sea usada por sistemas externos que comparten BD.
   - Eliminar los modelos (no necesariamente las tablas, para no perder datos históricos).
5. **Unificación de modelos duplicados:**
   - `DatoInversionista` / `DatosInversionistas` → unificar.
   - `WebPrecioBG` / `WebPreciosBG` → unificar.
   - `WebElemento` / `WebEstrategia` → unificar.
6. **Corrección de relación en Aportacion:**
   - Cambiar `hasOne` a `belongsTo` para `inversionista()`.
7. **Migración a Policies de Laravel:**
   - Crear Policies para cada entidad: `UserPolicy`, `InversionistaPolicy`, `MovimientoPolicy`, `SeriePolicy`, `NotaPolicy`.
   - Hacer que convivan con Spatie Permission (no reemplazar, complementar).
   - El middleware `PermissionCheck` delega en Policies cuando existen.

### Riesgos

- **Regresiones funcionales:** La fusión de controladores y migración de lógica a Services puede introducir bugs. Se requiere test suite.
- **Modelos huérfanos usados externamente:** Si las tablas son consumidas por otro sistema, eliminar los modelos puede no ser posible. Verificar antes de eliminar.
- **Convivencia Spatie + Policies:** Dos sistemas de autorización pueden generar confusión si no se documenta claramente cuál aplica en cada caso.

### Bloqueadores

- Ausencia de tests automatizados (dificulta verificar que la refactorización no rompió nada).
- Desconocimiento sobre si las tablas de modelos huérfanos son usadas por otros sistemas.

### Criterios de finalización

- [ ] Carpeta `app/DTOs/` unificada, sin imports rotos.
- [ ] Un solo controlador para Inversionistas (no dos).
- [ ] Repositorios implementados para al menos 4 entidades core.
- [ ] Modelos huérfanos y duplicados eliminados/deprecados.
- [ ] Relación `Aportacion.inversionista()` corregida.
- [ ] Policies implementadas para al menos 5 entidades.
- [ ] Todos los endpoints existentes funcionan sin regresiones.

---

## Fase 4: Refactorización Controlada — Calidad Interna

### Objetivo

Mejorar la calidad interna del código sin cambiar funcionalidad visible. Esto incluye: implementar Event-Driven Architecture para desacoplar lógicas, implementar audit trail completo, y normalizar naming de tablas.

### Dependencias

- Fase 3 completada (arquitectura estandarizada, base sólida para eventos).

### Entregables

1. **Event-Driven Architecture:**
   - Crear eventos de dominio: `InversionistaCreado`, `InversionistaActualizado`, `MovimientoRegistrado`, `MovimientoEliminado`, `RetiroCreado`, `RetiroAprobado`, `RetiroRechazado`, `ComisionCalculada`, `EDCSubido`, `AportacionRegistrada`.
   - Crear listeners para desacoplar lógicas que hoy están acopladas (ej: enviar notificaciones, recalcular comisiones, generar logs).
   - Migrar la lógica de `MovimientoObserver` a listeners del evento `MovimientoRegistrado`.
2. **Audit Trail completo:**
   - Extender logs a todas las entidades (no solo Inversionista y Movimiento).
   - Unificar formato de log (JSON con snapshot, usuario, IP, timestamp).
   - Implementar trait `Loggable` reusable.
3. **Normalización de naming de tablas (migraciones):**
   - `edc_x_mes` → `edcs`
   - `accesos_CRM` → `accesos_crm`
   - `CETS` → `cets`
   - (Solo si no hay sistemas externos que dependan de esos nombres.)
4. **Corrección de inconsistencias:**
   - `ConsolidarRetiroRequest`: crear el archivo faltante o eliminar el import.
   - `SendFcmTokenJob`: eliminar si no se usa, o documentar su propósito.

### Riesgos

- **Eventos en producción:** Si los listeners fallan silenciosamente, se pueden perder notificaciones o logs. Implementar manejo de errores robusto y dead letter queue.
- **Renombrar tablas:** Rompe cualquier sistema externo que lea directamente la BD. Requiere coordinación.

### Bloqueadores

- Sistemas externos que dependen de los nombres de tabla actuales.
- Falta de acuerdo sobre el formato unificado de logs.

### Criterios de finalización

- [ ] Al menos 8 eventos de dominio implementados con sus listeners.
- [ ] `MovimientoObserver` reemplazado por listeners de eventos.
- [ ] Audit trail cubriendo al menos 6 entidades.
- [ ] Tablas con naming inconsistente renombradas (o documentada la razón por la que no se pudo).
- [ ] `ConsolidarRetiroRequest` creado o import eliminado.

---

## Fase 5: Extensión Funcional (Mediano Plazo)

### Objetivo

Implementar los módulos funcionales recomendados que agregan valor al negocio: workflow de aprobación, dashboard operativo avanzado, CRM/Prospectos, importación de movimientos.

### Dependencias

- Fase 3 completada (arquitectura estandarizada para construir sobre base sólida).
- Fase 4 completada (eventos disponibles para desacoplar nuevas funcionalidades).

### Entregables

1. **Workflow de Aprobación:**
   - Motor de workflows multi-nivel para solicitudes de retiro.
   - Roles: Comercial → Dirección → Finanzas.
   - Trazabilidad de cada cambio de estado.
   - Notificaciones en cada transición (email + push).
2. **Dashboard Operativo Avanzado:**
   - KPIs con gráficos: AUM, captación neta, churn, comisiones por IB, pipeline de retiros.
   - Endpoints de agregación para frontend.
3. **CRM / Prospectos:**
   - Pipeline comercial: Prospecto → Lead → Cliente.
   - Seguimiento de interacciones.
   - Embudos de conversión.
4. **Importación de Movimientos (completar módulo parcial):**
   - Endpoints para carga de archivos de movimientos masivos.
   - Validación y pre-importación.
   - Confirmación y post-procesamiento.
5. **Módulo de Trading (si es requerido por negocio):**
   - Integración con market data (modelos Web*).
   - Órdenes de compra/venta.
   - (Solo si es una necesidad real; validar en Fase 0.)

### Riesgos

- **Scope creep:** Workflow y CRM pueden crecer indefinidamente. Definir MVP claro.
- **Trading:** Requiere integración con sistemas externos y expertise de dominio que puede no estar disponible.

### Bloqueadores

- Definición de procesos de aprobación por parte del negocio.
- Disponibilidad de datos de mercado para el módulo de Trading.

### Criterios de finalización

- [ ] Workflow de aprobación implementado para solicitudes de retiro con al menos 3 niveles.
- [ ] Dashboard con al menos 8 KPIs y endpoints de datos.
- [ ] CRM con pipeline básico (3 etapas).
- [ ] Importación de movimientos funcional (carga → validación → confirmación).
- [ ] Módulo de Trading: decisión Go/No-Go documentada; si Go, MVP implementado.

---

## Fase 6: Validación y Estabilización

### Objetivo

Garantizar que el sistema refactorizado es estable, que no hay regresiones, y que la documentación refleja el estado final.

### Dependencias

- Fases 0 a 5 completadas.

### Entregables

1. **Test Suite de Regresión:**
   - Tests de integración para los flujos core (crear inversionista → registro → contratos → fondeo → retiro).
   - Tests de endpoints críticos.
   - (No se exige 100% de cobertura, pero sí los happy paths y edge cases principales.)
2. **Pruebas de rendimiento:**
   - Benchmark de endpoints críticos bajo carga (usando Octane).
   - Verificación de queues (Horizon) sin cuellos de botella.
3. **Actualización documental final:**
   - OpenAPI actualizado con nuevos endpoints.
   - ADR actualizados con decisiones tomadas durante la refactorización.
   - Diagramas Mermaid actualizados al estado final.
4. **Manuales operativos:**
   - Guía de operación para administradores.
   - Guía de troubleshooting para DevOps.

### Riesgos

- **Fatiga del equipo:** Después de 5 fases, el equipo puede estar agotado. Planificar descansos entre fases.
- **Regresiones no detectadas:** Sin test suite previa, es difícil garantizar cero regresiones. Priorizar tests de humo.

### Bloqueadores

- Falta de entorno de staging idéntico a producción para pruebas de rendimiento.

### Criterios de finalización

- [ ] Test suite con al menos 30 tests (integración + endpoints).
- [ ] Pruebas de rendimiento ejecutadas y documentadas.
- [ ] Documentación OpenAPI, ADR y diagramas actualizados.
- [ ] Manual de operación y troubleshooting entregados.

---

# Planes

## Plan de Alcance

### Qué será incluido

- Los 33 módulos identificados en la auditoría.
- Los 14 módulos recomendados, priorizados según su criticidad.
- Las 6 fases descritas arriba.
- La documentación listada en "Documentación necesaria".

### Qué queda fuera (explícitamente)

- Microservicios: no se recomienda migrar a microservicios en el alcance de este plan. Se mantiene el monolito modular.
- Reescritura total: no se considera. La refactorización es incremental sobre el código existente.
- Cambios de framework: se permanece en Laravel 12.x. No se evalúa migrar a otro framework.
- Frontend: el alcance es exclusivamente back-end (API).
- Infraestructura: no se incluyen cambios de servidor, CI/CD, o cloud.
- Pruebas unitarias exhaustivas (100% coverage): solo tests de integración y endpoints críticos.

### Prioridades

Véase la sección "Tareas" y "Resumen Ejecutivo > Prioridades".

### Dependencias

Véase "Dependencias entre fases".

---

## Plan de Requerimientos

### Requerimientos técnicos

1. **PHP 8.3+** (actualizar desde ^8.1 para aprovechar mejoras de rendimiento y typing).
2. **Laravel 12.x** (mantener versión actual, actualizar parches).
3. **Redis** (ya configurado para queues y cache).
4. **MySQL/MariaDB** (mantener motor de BD actual).
5. **Laravel Octane** (ya configurado; validar uso en producción).
6. **Laravel Horizon** (ya configurado; asegurar persistencia de configuración).
7. **Proveedor KYC/AML** (a contratar; ej: Jumio, Onfido, Trulioo).
8. **Proveedor de firma electrónica** (a contratar; ej: DocuSign, SignNow).
9. **API bancaria** (STP/SPEI para conciliación automática; a contratar).
10. **Servicio de email transaccional** (ej: Resend ya está configurado en `config/resend.php`, o Mailgun, Postmark).
11. **Firebase Cloud Messaging** (ya integrado; mantener).

### Requerimientos funcionales

1. **KYC/AML:** Validación de identidad (INE, CURP, RFC), listas negras, scoring de riesgo.
2. **Firma electrónica:** Firma de contratos con validez legal, aplicable a Adhesión, Invitación, LPOA.
3. **Email transaccional:** Notificaciones de eventos críticos por correo electrónico.
4. **Scheduler:** Tareas programadas (limpieza de tokens, recordatorios, reportes).
5. **Workflow de aprobación:** Flujo multi-nivel para solicitudes de retiro.
6. **Dashboard operativo:** KPIs con gráficos, métricas de negocio.
7. **CRM:** Pipeline de prospectos, seguimiento comercial.
8. **Importación de movimientos:** Carga masiva de movimientos con validación.
9. **5 roles nuevos:** Comercial, Finanzas, MesaNegocios, AuxiliarAdministrativo, AnalisisProduccion.

### Requerimientos de infraestructura

1. **Servidor con cron job configurable** para Scheduler.
2. **Certificados SSL** para comunicación segura con proveedores externos.
3. **Almacenamiento seguro** para documentos KYC y contratos firmados (encriptados en reposo).
4. **Entorno de staging** para pruebas de regresión y rendimiento.
5. **Monitoreo de queues** (Horizon dashboard accesible para operaciones).

### Requerimientos documentales

Véase la sección "Documentación necesaria".

### Requerimientos para despliegue

1. **Estrategia de despliegue:** Blue-green o canary para minimizar downtime en refactorizaciones grandes.
2. **Rollback plan:** cada cambio debe ser reversible (migraciones reversibles, feature flags para funcionalidades nuevas).
3. **Feature flags:** recomendar uso de flags para activar/desactivar funcionalidades nuevas sin afectar a todos los usuarios.

### Requerimientos para pruebas

1. **Test suite de integración** para flujos core (happy paths y edge cases).
2. **Test suite de endpoints** (al menos endpoints críticos: auth, CRUD inversionistas, movimientos, retiros).
3. **Pruebas de rendimiento** con Octane bajo carga simulada.
4. **Pruebas de regresión** automatizadas en CI/CD.

---

## Plan Técnico

### Arquitectura encontrada

- **Monolito híbrido** con capas: Controllers → Services → Models (Eloquent) → DB.
- **Dos aproximaciones coexistentes:** Controladores con lógica directa (`InversionistasController`) vs controladores con Service Layer (`InversionistaController`, `MovimientoController`).
- **Sin Repository Pattern:** Services acceden directamente a Eloquent.
- **Sin Event-Driven Architecture:** Solo existe `Registered` de Laravel; `MovimientoObserver` como única reacción a eventos.
- **Autorización:** Spatie Permission con middleware custom (`PermissionCheck`). Sin Policies de Laravel.
- **DTOs:** spatie/laravel-data con dos carpetas inconsistentes (`DTO` y `DTOS`).
- **Autenticación dual:** Dos modelos Authenticatable (`User` para admin, `Inversionista` para cliente) con Sanctum.
- **Integraciones:** OpenPay (pagos), FCM (push), PayPal (manual), no hay más integraciones externas.

### Restricciones

1. **No se puede migrar a microservicios** en el alcance de este plan (decisión de negocio requerida).
2. **No se puede reescribir desde cero** (sistema en producción con datos vivos).
3. **Las migraciones de BD deben ser reversibles** y no destructivas.
4. **Cambios a la API deben ser backward-compatibles** o versionados (v2).
5. **Spatie Permission debe mantenerse funcional** durante la migración a Policies (convivencia, no reemplazo).

### Deuda técnica observable

1. **Arquitectura híbrida inconsistente:** Dos estilos de controladores coexisten sin un estándar.
2. **Modelos huérfanos:** 15+ modelos sin uso aparente, posiblemente legacy.
3. **Modelos duplicados:** `DatoInversionista`/`DatosInversionistas`, `WebPrecioBG`/`WebPreciosBG`, `WebElemento`/`WebEstrategia`.
4. **Carpetas DTO/DTOS:** Nomenclatura inconsistente.
5. **Relación incorrecta:** `Aportacion.inversionista()` usa `hasOne` en lugar de `belongsTo`.
6. **Sin Repository Pattern:** Services acoplados directamente a Eloquent.
7. **Scheduler vacío:** Kernel definido pero sin tareas.
8. **Código comentado:** `PermissionsCheck` tiene truncates comentados.
9. **Archivo faltante:** Import a `ConsolidarRetiroRequest` que no existe en el filesystem.
10. **Job sin uso:** `SendFcmTokenJob` definido pero no despachado en el código actual.
11. **Naming inconsistente de tablas:** `edc_x_mes`, `accesos_CRM`, `CETS`.
12. **Sin API Versioning formal:** v1 implícito por carpeta de rutas, no por headers.
13. **Endpoints duplicados:** Misma funcionalidad expuesta en dos controladores distintos (ej: movimientos vía `MovimientoController` y vía `InversionistasController`).

### Componentes críticos

1. **`InversionistasController`** (el más grande, contiene lógica de múltiples módulos). Cualquier refactorización aquí tiene alto impacto.
2. **`MovimientoObserver`** (recalcula comisiones automáticamente). Si se rompe, las comisiones de IBs se calcularían incorrectamente.
3. **`PermissionCheck` middleware** (toda la autorización del sistema depende de este middleware custom).
4. **`OpenPayService` + webhook** (flujo de pagos automatizado vía OpenPay).
5. **`FcmNotificationService` + Jobs** (único canal de notificaciones a clientes).
6. **Tabla `inversionistas`** (entidad central referenciada por casi todos los módulos).

### Integraciones

1. **OpenPay** (SDK openpay/sdk ^3.1): Cargos y webhooks. Es la única pasarela de pago con integración real.
2. **Firebase Cloud Messaging** (HTTP v1 API): Push notifications para app móvil. Autenticación por Service Account.
3. **PayPal** (manual): Solo registro, sin SDK. No hay integración real con PayPal API.
4. **Resend** (configurado en `config/resend.php` pero sin uso extensivo en el código actual).
5. **Google Auth** (para FCM Service Account).

### Módulos sensibles

1. **Solicitudes de Retiro:** Impacta directamente en el dinero de los clientes. Cualquier error puede tener consecuencias legales y financieras.
2. **Movimientos (compras/ventas):** Afecta saldos, comisiones, y libro de accionistas.
3. **Contratos:** Documentos legales. Cualquier modificación debe ser validada por el área legal.
4. **Aportaciones/OpenPay:** Manejo de dinero real vía pasarela de pago.

### Dependencias

- **Spatie Permission:** La autorización depende críticamente de este paquete.
- **DomPDF:** Generación de contratos y comprobantes.
- **PhpSpreadsheet:** Exportación de Excel para libro de accionistas.
- **spatie/laravel-data:** DTOs tipados (usado extensivamente en validación y respuestas).
- **milon/barcode:** Generación de QR para sellos digitales.
- **Laravel Sanctum:** Autenticación API.
- **Laravel Horizon:** Monitoreo de queues.
- **Laravel Octane:** Aceleración (configurado, uso en producción no confirmado).

### Decisiones técnicas importantes

1. **Por qué Spatie Permission y no Policies:** El middleware `PermissionCheck` permite validación dinámica sin necesidad de definir Policies por cada entidad. Es flexible pero menos granular.
2. **Por qué Sanctum y no Passport:** Para una SPA + app móvil, Sanctum es más ligero y suficiente. No se requiere OAuth2 completo.
3. **Por qué dos modelos Authenticatable:** Separación completa de sesiones admin y clientes. Cada uno tiene su propia tabla, tokens, y lógica de autenticación. Esto evita mezclar roles de admin con clientes en una sola tabla.
4. **Por qué Redis y no database para queues:** Redis ofrece mejor rendimiento para queues y ya está configurado con Horizon.
5. **Por qué DomPDF y no otra librería de PDFs:** DomPDF es maduro, soporta CSS/HTML, y se integra bien con Blade.

---

## Notas Técnicas

### Observaciones

1. El sistema está funcional. No está roto. La refactorización debe ser incremental y segura, no disruptiva.
2. La documentación generada en `DDocumentacion/` es exhaustiva y de gran valor. Debe mantenerse viva.
3. La ausencia de tests automatizados es un riesgo transversal a toda la refactorización. Se recomienda priorizar una test suite de regresión básica en la Fase 1.
4. `Laravel Octane` y `Horizon` ya están configurados. Si no se están usando en producción, habilitarlos podría mejorar el rendimiento sin cambios de código.
5. El comando `PermissionsCheck` con truncates comentados es un riesgo latente. El código comentado debe limpiarse.
6. Existen rutas duplicadas entre `routes/inversionista.php` y `routes/inversionistas.php`. Esto puede causar confusión y bugs sutiles.

### Posibles impactos

- **Unificar controladores:** Impacto alto en el frontend si cambian las firmas de los endpoints. Debe mantenerse backward compatibility.
- **Migrar a Policies:** Sin impacto en la API si se hace correctamente (convivencia). El middleware `PermissionCheck` puede delegar en Policies sin cambiar su interfaz.
- **Renombrar tablas:** Alto impacto en sistemas externos que lean la BD directamente.
- **Eliminar modelos huérfanos:** Bajo impacto si se verifica que no hay referencias. Las tablas pueden conservarse.

### Decisiones futuras (fuera del alcance de este plan, pero a considerar)

- ¿Microservicios? Si el sistema crece, separar módulos como Notificaciones o Pagos en servicios独立 podría ser beneficioso. Pero no ahora.
- ¿Cambio de motor de BD? MySQL/MariaDB es adecuado. No se recomienda cambiar.
- ¿Multi-tenant? Si se requiere para fondos locales vs foráneos, debería diseñarse como una capa de tenancy, no como sistemas separados.

### Supuestos

Véase la sección "Supuestos".

### Incertidumbres

1. ¿Existen sistemas externos que dependan de las tablas de modelos huérfanos?
2. ¿Qué nivel de KYC/AML es requerido por la regulación aplicable?
3. ¿Está contratado o planificado contratar un proveedor de firma electrónica?
4. ¿Está planificado implementar el módulo de Trading, o los movimientos administrativos son suficientes?
5. ¿Se usa Laravel Octane en producción?
6. ¿Los modelos Web* son alimentados por un sistema externo que sigue activo?

---

# Tareas

Todas las tareas listadas a continuación son **recomendaciones priorizadas**. No se asignan personas. El esfuerzo es estimado en días-hombre. El impacto se clasifica como Alto, Medio, Bajo. La criticidad indica qué tan indispensable es la tarea para la operación segura del sistema.

| # | Prioridad | Tarea | Fase | Dependencias | Esfuerzo est. | Impacto | Criticidad |
|---|-----------|-------|------|-------------|---------------|---------|------------|
| 1 | Crítica | Validar hallazgos de auditoría con stakeholders | 0 | Ninguna | 5 d/h | Alto | Alta |
| 2 | Crítica | Definir requisitos regulatorios KYC/AML con Legal | 0 | Tarea 1 | 3 d/h | Alto | Alta |
| 3 | Crítica | Contratar proveedor de KYC/AML | 2 | Tarea 2 | 10 d/h | Alto | Alta |
| 4 | Crítica | Contratar proveedor de firma electrónica | 2 | Tarea 1 | 5 d/h | Alto | Alta |
| 5 | Crítica | Implementar validación KYC/AML en flujo de registro | 2 | Tarea 3 | 15 d/h | Alto | Alta |
| 6 | Crítica | Implementar firma electrónica en contratos | 2 | Tarea 4 | 12 d/h | Alto | Alta |
| 7 | Alta | Generar especificación OpenAPI 3.0 de endpoints existentes | 1 | Ninguna | 15 d/h | Medio | Media |
| 8 | Alta | Implementar emails transaccionales (5 tipos) | 2 | Tarea 7 (para conocer endpoints) | 10 d/h | Medio | Alta |
| 9 | Alta | Activar Scheduler con tareas mínimas | 2 | Ninguna | 5 d/h | Medio | Alta |
| 10 | Alta | Crear 5 roles de negocio nuevos | 2 | Ninguna | 3 d/h | Bajo | Media |
| 11 | Alta | Documentar ADR (al menos 5 decisiones) | 1 | Tarea 1 | 8 d/h | Medio | Media |
| 12 | Alta | Unificar carpetas DTO/DTOS | 3 | Fase 2 completada | 3 d/h | Bajo | Media |
| 13 | Alta | Unificar controladores de Inversionistas | 3 | Fase 2 completada | 20 d/h | Alto | Alta |
| 14 | Alta | Implementar Repository Pattern (4 entidades core) | 3 | Tarea 13 | 15 d/h | Alto | Media |
| 15 | Alta | Eliminar/deprecar modelos huérfanos | 3 | Verificación de dependencias externas | 3 d/h | Medio | Media |
| 16 | Alta | Unificar modelos duplicados | 3 | Tarea 15 | 3 d/h | Medio | Media |
| 17 | Alta | Corregir relación hasOne → belongsTo en Aportacion | 3 | Tarea 14 | 1 d/h | Bajo | Media |
| 18 | Alta | Migrar a Policies de Laravel (conviviendo con Spatie) | 3 | Tarea 13 | 12 d/h | Alto | Media |
| 19 | Alta | Implementar Workflow de Aprobación | 5 | Fases 3 y 4 | 20 d/h | Alto | Alta |
| 20 | Alta | Implementar Dashboard Operativo Avanzado | 5 | Fase 3 | 15 d/h | Medio | Media |
| 21 | Media | Crear eventos de dominio (8 eventos) | 4 | Fase 3 | 8 d/h | Alto | Media |
| 22 | Media | Migrar MovimientoObserver a listeners de eventos | 4 | Tarea 21 | 5 d/h | Alto | Media |
| 23 | Media | Extender audit trail a 6+ entidades | 4 | Tarea 22 | 8 d/h | Medio | Baja |
| 24 | Media | Renombrar tablas con naming inconsistente | 4 | Verificación de dependencias externas | 5 d/h | Medio | Baja |
| 25 | Media | Crear/eliminar ConsolidarRetiroRequest según corresponda | 4 | Ninguna | 1 d/h | Bajo | Baja |
| 26 | Media | Implementar CRM/Prospectos (MVP) | 5 | Fase 3 | 20 d/h | Medio | Media |
| 27 | Media | Completar módulo de Importación de Movimientos | 5 | Fase 3 | 10 d/h | Medio | Baja |
| 28 | Media | Implementar test suite de integración (30+ tests) | 6 | Fases 1-5 | 20 d/h | Alto | Alta |
| 29 | Baja | Evaluar y decidir sobre módulo de Trading (Go/No-Go) | 5 | Fase 1 | 3 d/h | Alto | Baja |
| 30 | Baja | Implementar webhooks salientes | 5 | Tarea 21 | 10 d/h | Medio | Baja |
| 31 | Baja | Implementar API de Market Data | 5 | Verificación de modelos Web* | 10 d/h | Bajo | Baja |
| 32 | Baja | Implementar Gestión Documental avanzada | 5 | Fase 3 | 15 d/h | Bajo | Baja |
| 33 | Baja | Implementar observabilidad y métricas de negocio | 5 | Fase 5 | 10 d/h | Medio | Baja |
| 34 | Baja | Actualizar documentación a estado final | 6 | Fase 5 | 8 d/h | Medio | Baja |
| 35 | Baja | Manuales operativos y de troubleshooting | 6 | Fase 5 | 5 d/h | Bajo | Baja |

**Esfuerzo total estimado:** ~330 días-hombre (aproximadamente 16.5 meses-hombre para un desarrollador full-time; o ~3.3 meses con un equipo de 5 desarrolladores trabajando en paralelo donde las dependencias lo permitan).

---

# Riesgos

## Alto

| # | Descripción | Impacto | Probabilidad | Mitigación sugerida |
|---|-------------|---------|-------------|---------------------|
| R1 | **Sin KYC/AML: riesgo regulatorio.** El sistema opera sin validación de identidad ni listas negras, exponiendo a la empresa a sanciones. | Muy Alto | Certeza (ya es un hecho) | Implementar KYC/AML en Fase 2. Contratar proveedor especializado. Definir requisitos legales con el área de compliance. |
| R2 | **Firma no vinculante:** Contratos firmados con PNG no tienen validez legal plena. | Alto | Certeza | Implementar firma electrónica vinculante en Fase 2. Determinar tratamiento de contratos existentes con PNG. |
| R3 | **Conciliación manual:** Errores en registro de fondeos pueden causar discrepancias financieras. | Alto | Alta (proceso manual) | Integrar conciliación bancaria automática vía API (STP/SPEI) en Fase 5. Mientras tanto, implementar doble validación en movimientos. |
| R4 | **Regresiones en refactorización de controladores:** Unificar `InversionistaController` e `InversionistasController` puede romper endpoints si no hay test suite. | Alto | Alta | Priorizar test suite de regresión antes de la unificación. Hacer la migración con feature flags y despliegue canary. |
| R5 | **Pérdida de notificaciones si FCM falla:** Sin canal alternativo (email), eventos críticos pueden no ser comunicados al cliente. | Alto | Media | Implementar email transaccional como canal alternativo en Fase 2. |

## Medio

| # | Descripción | Impacto | Probabilidad | Mitigación sugerida |
|---|-------------|---------|-------------|---------------------|
| R6 | **Modelos huérfanos usados externamente:** Si otros sistemas leen las tablas de modelos a eliminar, la eliminación de modelos podría romper integraciones. | Medio | Media | Auditar dependencias externas antes de eliminar modelos. Mantener tablas, solo eliminar modelos Eloquent. |
| R7 | **Scheduler sin tareas:** No hay recordatorios automáticos, limpieza de tokens, ni reportes periódicos. | Medio | Certeza | Activar Scheduler con tareas mínimas en Fase 2. |
| R8 | **Roles insuficientes:** Solo 4 roles (Admin, Direccion, IB, Otro) no cubren las necesidades operativas reales. | Medio | Certeza | Crear 5 roles adicionales en Fase 2. |
| R9 | **Conflicto Spatie Permission vs Policies:** Dos sistemas de autorización pueden generar confusión. | Medio | Media | Documentar claramente la convivencia. Establecer regla: Policies para autorización de entidad, Spatie para permisos de módulo. |
| R10 | **Renombrar tablas rompe integraciones externas:** | Medio | Media | Coordinar con dueños de sistemas externos. Usar migraciones con feature flags. |

## Bajo

| # | Descripción | Impacto | Probabilidad | Mitigación sugerida |
|---|-------------|---------|-------------|---------------------|
| R11 | **Código comentado en PermissionsCheck:** Los truncates comentados pueden inducir a error si alguien los descomenta sin entender las consecuencias. | Bajo (controlable) | Baja | Limpiar código comentado en Fase 4. Documentar el propósito del comando. |
| R12 | **ConsolidarRetiroRequest faltante:** El import existe pero el archivo no. Si se intenta usar, causará error. | Bajo | Baja | Crear el archivo o eliminar el import en Fase 4. |
| R13 | **Job SendFcmTokenJob sin uso:** Código muerto que genera confusión. | Bajo | Baja | Eliminar job o documentar su propósito futuro en Fase 4. |
| R14 | **Falta de API Versioning formal:** Si se requieren cambios breaking en la API, no hay mecanismo para versionar. | Bajo (hoy no hay breaking changes planeados) | Media | Implementar versionado por headers en Fase 3. |

---

# Posibles Bloqueadores

1. **Documentación insuficiente de procesos de negocio:** Si los stakeholders no pueden describir cómo debe funcionar el workflow de aprobación, no se puede implementar.
2. **Reglas de negocio desconocidas:** El cálculo de comisiones está implementado en el Observer, pero ¿hay reglas adicionales no documentadas (ej: topes, bonos por desempeño)?
3. **Procesos no documentados:** El fondeo vía PayPal es manual. ¿Hay un procedimiento operativo estándar que deba reflejarse en el sistema?
4. **Dependencias externas no confirmadas:**
   - APIs de KYC/AML (proveedor no contratado).
   - API de firma electrónica (proveedor no contratado).
   - API bancaria STP/SPEI (integración no contratada).
5. **Sistemas heredados:** Los modelos Web* sugieren un dashboard público externo que comparte la BD. Si ese sistema existe, cualquier cambio en tablas compartidas debe coordinarse.
6. **Falta de responsables funcionales:** No hay Product Owner ni dueños de proceso identificados en la documentación actual.
7. **Datos inconsistentes:** Existen modelos duplicados apuntando a la misma tabla. Si ambos se usaron históricamente, puede haber datos en estados inconsistentes.
8. **Integraciones bancarias no disponibles:** La conciliación automática depende de APIs que pueden no estar disponibles para la empresa.
9. **Entorno de staging inexistente o no equivalente a producción:** Sin un entorno de pruebas representativo, las refactorizaciones son riesgosas.
10. **Ausencia de tests:** Sin test suite, cualquier refactorización es a ciegas. Este es el bloqueador más transversal.

---

# Hitos

## Hito 1: Auditoría Validada

- **Objetivo:** Hallazgos de la auditoría confirmados con stakeholders.
- **Entregable:** Acta de validación y mapa de stakeholders.
- **Dependencias:** Ninguna.
- **Criterio de aceptación:** Al menos un representante de cada área (comercial, finanzas, legal, sistemas) ha revisado y validado los hallazgos.

## Hito 2: Documentación Base Publicada

- **Objetivo:** Documentación técnica viva disponible para el equipo.
- **Entregable:** OpenAPI spec, ADR, diagramas Mermaid, README.md.
- **Dependencias:** Hito 1.
- **Criterio de aceptación:** Un nuevo desarrollador puede configurar el entorno local y entender la arquitectura usando solo la documentación.

## Hito 3: Cumplimiento Regulatorio Mínimo Alcanzado

- **Objetivo:** KYC/AML y firma electrónica funcionando en producción.
- **Entregable:** Flujo de registro con validación KYC. Contratos con firma electrónica vinculante.
- **Dependencias:** Hito 1. Contratación de proveedores externos.
- **Criterio de aceptación:** Todo nuevo inversionista pasa por KYC antes de invertir. Todo contrato nuevo se firma electrónicamente.

## Hito 4: Automatización Operativa Básica

- **Objetivo:** Scheduler activo y emails transaccionales funcionando.
- **Entregable:** 3+ tareas programadas en cron. 5+ tipos de email transaccional.
- **Dependencias:** Hito 2 (para conocer endpoints). Configuración de cron en servidor.
- **Criterio de aceptación:** Tareas programadas ejecutándose diariamente. Emails llegando a los clientes para eventos críticos.

## Hito 5: Arquitectura Unificada

- **Objetivo:** Estándar consistente en controladores, servicios, repositorios, DTOs y Policies.
- **Entregable:** Un solo controlador de Inversionistas. Repositorios para 4 entidades. Policies para 5 entidades. Modelos huérfanos eliminados.
- **Dependencias:** Hito 3 y 4. Test suite de regresión.
- **Criterio de aceptación:** No existen controladores con lógica directa. Todos los accesos a datos pasan por repositorios. La autorización usa Policies.

## Hito 6: Sistema Desacoplado con Eventos

- **Objetivo:** Event-Driven Architecture para lógicas desacopladas.
- **Entregable:** 8 eventos de dominio con sus listeners. Observer reemplazado. Audit trail extendido.
- **Dependencias:** Hito 5.
- **Criterio de aceptación:** Las notificaciones, logs y recálculo de comisiones se disparan por eventos, no por código acoplado.

## Hito 7: Funcionalidad Extendida

- **Objetivo:** Workflow de aprobación, dashboard, CRM, importación de movimientos.
- **Entregable:** Módulos nuevos funcionales.
- **Dependencias:** Hito 5. Definiciones de negocio.
- **Criterio de aceptación:** Workflow multi-nivel funcionando. Dashboard con 8+ KPIs. CRM con pipeline básico. Importación funcional.

## Hito 8: Sistema Estabilizado y Validado

- **Objetivo:** Test suite, pruebas de rendimiento, documentación final.
- **Entregable:** 30+ tests. Reporte de rendimiento. Documentación actualizada. Manuales operativos.
- **Dependencias:** Hitos 1-7.
- **Criterio de aceptación:** Test suite pasa en CI/CD. Pruebas de rendimiento sin degradación. Documentación refleja el estado real del sistema.

---

# Fechas Tentativas

**Advertencia explícita:** Todas las fechas son estimaciones basadas en la complejidad observada, el tamaño del sistema (33 módulos, 37 modelos, 76 endpoints), y la deuda documental identificada. Están sujetas al resultado de la auditoría completa (Fase 0) y a la disponibilidad de recursos. No constituyen compromisos.

| Fase | Duración estimada | Período tentativo | Hito asociado |
|------|-------------------|-------------------|---------------|
| Fase 0: Descubrimiento | 2-3 semanas | 17 Jun – 8 Jul 2026 (Sem 1-3) | Hito 1 |
| Fase 1: Documentación | 3-4 semanas | 25 Jun – 22 Jul 2026 (Sem 2-5) | Hito 2 |
| Fase 2: Estabilización | 4-5 semanas | 9 Jul – 19 Ago 2026 (Sem 4-9) | Hito 3, Hito 4 |
| Fase 3: Estandarización | 4-5 semanas | 6 Ago – 10 Sep 2026 (Sem 8-12) | Hito 5 |
| Fase 4: Refactorización | 3-4 semanas | 27 Ago – 24 Sep 2026 (Sem 11-15) | Hito 6 |
| Fase 5: Extensión | 4-6 semanas | 10 Sep – 15 Oct 2026 (Sem 13-18) | Hito 7 |
| Fase 6: Validación | 2-3 semanas | 1 Oct – 17 Oct 2026 (Sem 16-18) | Hito 8 |

**Total estimado:** 4 meses (17 Jun – 17 Oct 2026) con equipo de 4-5 desarrolladores trabajando en paralelo donde las dependencias lo permitan.

**Nota sobre paralelismo:** Las Fases 1 y 2 pueden solaparse parcialmente (documentar mientras se implementan mejoras de estabilización). Las Fases 3 y 4 son secuenciales. La Fase 5 puede iniciar parcialmente una vez que Fase 3 esté completada para algunos módulos.

---

# Dependencias entre fases

| Fase Origen | Fase Destino | Dependencia | Criticidad |
|-------------|-------------|-------------|------------|
| Fase 0 | Fase 1 | Requerimientos validados necesarios para documentar correctamente | Alta |
| Fase 0 | Fase 2 | Requisitos regulatorios definidos y proveedores contratados | Crítica |
| Fase 1 | Fase 2 | Documentación de endpoints existentes para no romper nada al modificar | Media |
| Fase 1 | Fase 3 | OpenAPI spec necesaria para verificar regresiones en refactorización | Alta |
| Fase 2 | Fase 3 | Sistema estable regulatoria y operativamente antes de refactorizar | Alta |
| Fase 2 | Fase 5 | Roles y Scheduler son base para workflow y CRM | Media |
| Fase 3 | Fase 4 | Arquitectura unificada es prerequisito para implementar eventos | Alta |
| Fase 3 | Fase 5 | Arquitectura estandarizada necesaria para construir nuevos módulos sin aumentar deuda | Alta |
| Fase 4 | Fase 5 | Eventos disponibles para desacoplar nuevos módulos | Media |
| Fase 5 | Fase 6 | Funcionalidad completa antes de validación final | Alta |
| Fase 1 | Fase 6 | Documentación debe actualizarse en cada fase | Media |
| Fase 3 | Fase 6 | Test suite de regresión depende de APIs estables | Alta |

---

# Documentación necesaria

La siguiente documentación debería existir o actualizarse durante el proyecto. La generada en `DDocumentacion/` es un excelente punto de partida.

1. **Arquitectura del Sistema** — Mantener actualizado `arquitectura.md`.
2. **ADR (Architecture Decision Records)** — Crear carpeta `docs/adr/` con al menos 5 decisiones documentadas:
   - ADR-001: Elección de Sanctum sobre Passport
   - ADR-002: Elección de Spatie Permission sobre Policies nativas
   - ADR-003: Estrategia de autenticación dual (User + Inversionista)
   - ADR-004: Elección de DomPDF sobre alternativas (wkhtmltopdf, Headless Chrome)
   - ADR-005: Decisión de mantener monolito vs microservicios
3. **Diagramas Mermaid** — Mantener actualizados en el repositorio:
   - Diagrama de arquitectura
   - Diagrama de entidades (ERD)
   - Diagrama de procesos (flujos core)
   - Diagrama de módulos
4. **Entidades y Modelos** — Mantener actualizado `entidades.md` con cambios de la Fase 3.
5. **API Reference (OpenAPI 3.0)** — Especificación interactiva generada automáticamente (scramble/scribe) o manual.
6. **Colección Postman** — Mantener actualizada con todos los endpoints, variables de entorno y tests.
7. **Casos de Uso** — Documentar los 10 procesos principales (flujos de negocio) identificados en `procesos.md`.
8. **Reglas de Negocio** — Extraer reglas implícitas en el código (ej: cálculo de comisiones, validación de QR) a un documento explícito.
9. **Flujos de Trabajo** — Documentar los flujos de aprobación y onboarding.
10. **Módulos** — Mantener actualizado `modulos.md`.
11. **Integraciones** — Documentar contratos de integración con OpenPay, FCM, PayPal, y futuros (STP, KYC, Firma).
12. **Scheduler / Tareas Programadas** — Documentar cada tarea, su frecuencia, propósito y dependencias.
13. **Jobs y Queues** — Documentar cada Job, su queue, reintentos y propósito.
14. **Policies y Permisos** — Mantener actualizado `roles.md` con los nuevos roles y la matriz de permisos.
15. **Roles y Responsabilidades** — Mapa de roles de negocio ↔ roles del sistema.
16. **Riesgos** — Mantener un registro de riesgos vivo (no solo este plan inicial).
17. **Bitácoras Técnicas** — Registrar decisiones de arquitectura y cambios significativos durante la refactorización.
18. **Manuales Operativos** — Para administradores del sistema y DevOps.
19. **Guía de Onboarding** — Para nuevos desarrolladores (en README.md).
20. **Guía de Troubleshooting** — Problemas comunes y sus soluciones.

---

# Supuestos

## Confirmado

- El sistema está en producción y funcionando para operación manual. *(Evidencia: LastContextSesion.md)*
- Existen 33 módulos (20 completos, 9 parciales, 2 faltantes, 2 esperados no encontrados). *(Evidencia: modulos.md)*
- La arquitectura es un monolito híbrido con Service Layer parcial. *(Evidencia: arquitectura.md)*
- Hay 37 modelos, de los cuales al menos 15 no tienen uso en la API. *(Evidencia: entidades.md, FilesMap.md)*
- La autorización usa Spatie Permission con middleware custom `PermissionCheck`. *(Evidencia: roles.md, código fuente)*
- No existen Policies de Laravel. *(Evidencia: arquitectura.md, FilesMap.md)*
- El Scheduler está vacío. *(Evidencia: app/Console/Kernel.php)*
- Hay 76 endpoints documentados. *(Evidencia: endpoints.md)*
- La integración con OpenPay y FCM está implementada y funcional. *(Evidencia: modulos.md, procesos.md)*
- PayPal se maneja manualmente sin SDK. *(Evidencia: modulos.md, procesos.md)*
- Existen dos modelos Authenticatable: `User` e `Inversionista`. *(Evidencia: entidades.md, arquitectura.md)*

## Inferido

- Los modelos Web* pertenecen a un dashboard web público legacy que comparte la BD. *(Inferido de: nombres de tablas, falta de uso en API, estructura de datos de mercado.)*
- El módulo de Trading no es prioritario porque los movimientos administrativos manuales cubren la necesidad actual. *(Inferido de: el sistema está operativo sin Trading.)*
- La falta de Scheduler se debe a que el sistema se opera de forma reactiva (a demanda) más que proactiva (automática). *(Inferido de: Kernel vacío.)*
- Los modelos huérfanos son vestigios de un sistema anterior o de migraciones de datos. *(Inferido de: naming inconsistente, falta de relaciones, falta de uso en código.)*
- `SendFcmTokenJob` fue creado para una funcionalidad futura o ya deprecada. *(Inferido de: está definido pero no se despacha en el código actual.)*

## Pendiente de validar

1. ¿Existen sistemas externos que dependan de las tablas de modelos huérfanos?
2. ¿Qué nivel de KYC/AML exige la regulación aplicable? ¿CNBV, CONDUSEF, otra?
3. ¿Está contratado o en proceso un proveedor de firma electrónica?
4. ¿Está contratado o en proceso un proveedor de KYC/AML?
5. ¿Se planea implementar el módulo de Trading o los movimientos administrativos son suficientes?
6. ¿Se usa Laravel Octane en producción o solo está configurado?
7. ¿Los modelos Web* son alimentados por un sistema externo activo?
8. ¿Existen reglas de negocio para IB no implementadas (topes, bonos, niveles)?
9. ¿El proceso de fondeo manual tiene un procedimiento operativo estándar que deba automatizarse?
10. ¿Se requiere segregación regulatoria real para fondos foráneos (módulo Inversora Foráneo)?
11. ¿Hay un responsable funcional (Product Owner) designado para este sistema?
12. ¿Existe un entorno de staging representativo para pruebas?
13. ¿Se requiere API versioning formal (v1, v2) o el versionado por carpeta de rutas es suficiente?
14. ¿Hay planes de migración a microservicios a futuro que este plan deba considerar?

---

# Restricciones adicionales

Reiterando las establecidas en el documento de requerimientos:

- No modificar código.
- No generar código.
- No proponer implementaciones concretas.
- No evaluar estilo del código.
- No evaluar PSR.
- No evaluar cobertura.
- No evaluar pruebas.
- No generar migraciones.
- No generar refactorizaciones automáticas.
- No generar commits.
- No generar PRs.

El único entregable permitido es `ProjectActionPlan.md`.

Todas las recomendaciones están justificadas por evidencia encontrada en la documentación (`DDocumentacion/`) o en el análisis del proyecto, diferenciando claramente entre hechos observados e inferencias razonables.

---

**Fin del documento.**
