# Registro de cambios del complemento import2calendar

>**IMPORTANTE**
Si no hay información sobre la actualización, es porque esta se refiere únicamente a la actualización de la documentación, la traducción o el texto.

# 27/09/2026 Beta 1.5.0
- Inicio y fin de los eventos personalizados ajustables al minuto (de 0 a 360 min)
- La actualización de las agendas consume muchos menos recursos, sobre todo en el caso de las agendas con gran volumen de datos
- Un calendario con un error ya no impide la actualización de los calendarios siguientes
- Mensajes de error más precisos en los registros
- Corrección de notas y lugares que se pierden cuando contienen una coma o un punto y coma
- Corrección de las recurrencias mensuales del tipo «segundo lunes» o «último viernes», que no aparecían en los comandos para los próximos días
- Se ha corregido el error que provocaba que la importación se detuviera en determinados eventos recurrentes
- Corrección de eventos antiguos sin fecha de finalización que se habían conservado por error
- Se ha corregido un error que provocaba que no se reconocieran correctamente algunas zonas horarias (Australia, Sudamérica, México…), lo que impedía la importación o provocaba desajustes en los horarios
- Un huso horario desconocido ya no interrumpe la importación: en su lugar se utiliza el de Jeedom
- Se ha corregido un problema por el que se creaba un evento duplicado cada vez que se actualizaba en Jeedom en italiano; ahora, un mensaje indica los duplicados que hay que eliminar.

# 25/02/2026 Beta 1.4.8
- Corrección de un error en la comprobación de la fecha

# 05/02/2026 Beta 1.4.7
- Autorización para cambiar el nombre de la agenda

# 04/10/2025 Estable 1.4.2
- Corrección de un error con las comillas dobles en el nombre del evento

# 16/06/2025 beta 1.4.1
- Corrección de un error en Cron

# 23/05/2025 Estable 1.4.0
- Versión mínima de Jeedom: 4.4
- Versión mínima de Debian: 11

# 07/05/2025 Beta 1.3.6
- Descarga del archivo iCal antes de su procesamiento

# 25/04/2025 Beta 1.3.5
- Incorporación de un tiempo de espera para las solicitudes
- Incorporación de un reintento para las solicitudes
- Corrección del calendario de reservas

# 09/04/2025 Beta 1.3.4
- Pequeñas correcciones

# 05/04/2025 Beta 1.3.3
- Corrección de un error en ical2calendar relacionado con los eventos del día -1 al día +7

# 06/03/2025 Beta 1.2.8
- Corrección de un error en ical2calendar
- Se han añadido los pedidos j-1, así como del j+2 al j+7
- Tratar las ocurrencias que se repiten según el día y no según la fecha

# 21/02/2025 Beta 1.2.7
- Corrección de la zona horaria
- Se ha añadido el comando «Actualizar»

# 21/02/2025 Beta 1.2.6
- Corrección de las fechas de exclusión e inclusión
- Corrección de cmd hoy si hay algún evento cada dos semanas

# 11/02/2025 Beta 1.2.5
- Creación de comandos «hoy» y «mañana» en el complemento Agenda para los eventos de tu iCal.
- Corrección de las fechas de las jornadas
- Otras pequeñas correcciones

# 03/02/2025 Beta y Estable 1.2.0
- Corrección si la zona horaria está mal formateada

# 30/01/2025 Beta 1.1.9
- Corrección en un evento recurrente semanal procedente de un calendario de Infomaniak

# 24/01/2025 Estable 1.1.8
- Corrección de **otros** en las acciones

# 09/01/2025 Beta 1.1.7
- Corrección de la actualización del calendario con fechas excluidas

# 07/01/2024 Beta 1.1.6
- Corrección de la hora de finalización de la jornada completa

# 06/11/2024 Estable 1.1.5
- Incorporación de las vacaciones escolares de los departamentos y territorios de ultramar

# 31/10/2024 Estable 1.1.4
- Corrección de frecuencia (véase el documento)

# 21/10/2024 Estable 1.1.3
- Corrección: si el evento incluye una alarma, se modificaban el título y la descripción.
- Se ha corregido la importación de enlaces webcal.

# 16/10/2024 Estable 1.1.2
- Corrección en la recuperación de eventos de varios años

# 07/10/2024 Estable 1.1.1
- Corrección si hay una coma en el nombre del evento

# 03/10/2024 Estable 1.1.0
- Corrección al eliminar o mover un evento presente en una recurrencia

# 01/10/2024 Beta 1.0.9
- Correcciones de avisos de PHP
- Se ha añadido el número de versión del complemento
- Corrección en la actualización del evento

# 17/08/2024 Estable 1.0.8
- Traducción al inglés, alemán, español, italiano y portugués. Gracias, @mips

# 06/05/2024 Estable 1.0.7
- Corregir el error «setTime».

# 06/05/2024 Beta 1.0.6
- Se ha añadido la posibilidad de establecer la hora de inicio y fin de un evento.

# 01/05/2024 Beta 1.0.5
- Tener en cuenta las fechas excluidas en las recurrencias
- Toma en cuenta las fechas modificadas en las recurrencias

# 29/04/2024 Estable 1.0.0
- Conversión de emojis a HTML (visible en el nombre del evento y en la descripción (JeeMate v3))
- Conversión de zonas horarias al formato de Windows (estilo «Romance Standard Time»)
- Se han añadido opciones para las acciones (todas, otras, evento)
- Consideración de la ubicación (visible en JeeMate v3)
- Posibilidad de configurar una hora de inicio y fin diferentes.

# 25/04/2024 Beta 0.8.0
- Eliminación de emojis del nombre del evento (error MySQL 22007)

# 25/04/2024 Estable 0.7.0
- Prueba a volver al documento de Jeedom

# 01/04/2024 Beta 0.6.0
- Se ha añadido el botón «Documentación» y «Registro de cambios»
- Se ha añadido un botón para acceder a Discord (en el Discord de JeeMate, con una sala dedicada)

# 30/03/2024 Estable 0.5.0
- primera versión estable

# 29/03/2024 Beta 0.5.0
- corrección del color del texto de entrada en el modo oscuro + pequeño cambio visual
- color personalizado, sin distinguir entre mayúsculas y minúsculas
- Eventos con repetición: si el último evento de la serie tiene más de tres días de antigüedad, la serie no se muestra.

# 28/03/2024 Beta 0.4.0
- Corrección de la zona horaria
- Incorporación de las descripciones (visibles en JeeMate)
- Gestión de tareas periódicas
- Gestión del fin de la recurrencia, por fecha o por número de repeticiones
- Gestión específica del color para determinados eventos

# 12/03/2024 Beta 0.3.0
- Corrección para la compatibilidad con Jeedom 4.3
- Color del texto y del fondo definidos por defecto

# 11/03/2024 Beta 0.1.0
- primera versión beta
