# Complemento import2calendar

El complemento sirve para importar un calendario en formato iCal al complemento oficial de Jeedom «Agenda» (calendar), por lo que este último debe estar instalado y configurado.
## Requisitos previos
- Complemento Agenda
- Complemento import2calendar

## Atención
**No es posible realizar modificaciones en el archivo iCal; se recopila la información del iCal para enviarla al complemento «Agenda» de Jeedom. No realices ninguna modificación en la agenda creada en el complemento «Agenda», ya que se borrarían con la próxima actualización de tu iCal.**


# <u>Configuración</u>

## Crear un dispositivo
Empieza por añadir un dispositivo y elige su nombre
### Parámetros de importación
- **ical**: indica la URL del archivo ical que se va a convertir.
- **Hora de inicio forzada**: elige una hora de inicio para todos los eventos del calendario. Por defecto, se utilizarán las horas de inicio del evento guardadas en el archivo iCal.
- **Hora de finalización forzada**: elige una hora de finalización para todos los eventos del calendario. Por defecto, se utilizarán las horas de finalización del evento guardado en el iCal.
- **ical auto**: vacaciones en Francia y días festivos. Si se selecciona esta opción, no hay que indicar nada en el campo ical.
- **cron**: elige el tiempo de actualización deseado para el calendario.

### Ajustes de visualización
- **icono**: el icono que se aplicará a cada evento.
- **color de fondo**: color de fondo predeterminado para cada evento.
- **color del texto**: color predeterminado del texto para cada evento.

### Personalización de eventos
Aquí puedes personalizar algunos eventos de tu calendario iCal.

- Color de fondo y color del texto.

Aquí, por defecto, no se ha modificado nada; estas opciones permiten cambiar la hora de inicio y de finalización para, por ejemplo, anticipar las acciones programadas.
- Hora de inicio: el evento comenzará X horas antes.
- Hora de finalización: el evento finalizará X horas después.

### Acciones de inicio y fin
A todos los eventos de tu calendario se les añadirán las acciones definidas aquí.
Puedes reorganizar las acciones arrastrándolas y soltándolas.


![Configuraciones de las acciones](../images/import2calendar_screenshot03.png)

En el campo **nombre**, puedes indicar el evento para el que está prevista la acción.
- Acepta un nombre parcial
- No tiene en cuenta las mayúsculas

**1** y **2**: déjalos en blanco o escribe **all** para que la acción se añada a todos los eventos de la agenda.
**4** - Selecciona **otros** para que la acción se añada a todos los eventos del calendario, excepto a aquellos para los que se haya previsto una acción personalizada.
**3** y **5**: introduce el **nombre del evento** para que la acción solo se añada a ellos.


Ahora puedes hacer clic en **guardar**.
La agenda correspondiente se creará en el complemento de agenda.

Ejemplo:

Aquí vemos los eventos del complemento Agenda; además, se puede observar que también he personalizado el color.

![Agenda](../images/Agenda-exemple.png)

La configuración en el complemento import2calendar

![Colores](../images/personnalisation-couleurs.png)

![Acciones](../images/personnalisation-actions.png)

Volvemos a la agenda para comprobar las acciones
![Calendario de revisiones](../images/import2calendarActions.gif)

## Edición de un dispositivo
Si modificas alguna de las siguientes opciones:
- icono
- color de fondo
- color del texto
- primeros pasos
- acciones finales

Los eventos se modificarán en el calendario

## Gestión de eventos
Cada vez que se realiza una copia de seguridad o cada vez que el cron definido analiza el archivo iCal, si un evento ya no aparece en dicho archivo, se elimina del calendario.
Los eventos pasados de más de 3 días no se importan y se irán eliminando progresivamente.

## Ocurrencias
Las reglas definidas en tu ical se convierten al formato de Jeedom Agenda. No he probado todas las posibilidades, así que si alguna no funciona, por favor, adjunta la línea correspondiente del registro de import2calendar: **event options** (configura tus registros en modo «warning» o «debug»).


Los eventos incluidos en la ocurrencia siguen siendo visibles en el calendario mientras la ocurrencia sea válida.

Ejemplo: 1 evento cada 5 días desde el 1 de marzo de 2024 hasta el 24 de noviembre de 2024. Todos los eventos se pueden ver en el calendario hasta el 27 de noviembre de 2024 (3 días después de que finalice la ocurrencia).

## Complemento Agenda
En tu agenda se añadirán avisos sobre los próximos eventos.
Así pues, dispondrás de 9 nuevos comandos:
- ayer
- hoy
- mañana
- pasado mañana
- día 3
- día 4
- día 5
- día 6
- d+7

## ical <-> Jeedom
En algunos casos, no será posible convertir al formato Jeedom, por lo que tendrás que adaptar tus calendarios.
Es el caso, por ejemplo, de esto:
- los martes y miércoles cada tres semanas
Para que esto se refleje en Jeedom, tienes que crear:
- los martes cada tres semanas
- los miércoles cada tres semanas

# <u>JeeMate</u>
- La descripción y la ubicación aparecerán en el calendario importado a JeeMate.

# <u>Atención</u>
- El nombre de la agenda creada es el mismo que el del dispositivo + «-ical» (ahora es posible modificarlo   )
- la habitación será idéntica

# <u>Asistencia técnica</u>
- Comunidad Jeedom
- Discord JeeMate

# <u>Solicitud de ayuda</u>
Para facilitarme la tarea a la hora de depurar un error de conversión, os pediría que creaseis un calendario de pruebas que incluya únicamente el evento que plantea el problema y que me dierais acceso a ese ICAL.


# <u>Agradecimientos</u>
El complemento y la asistencia técnica son gratuitos, pero si queréis invitarme a un café o regalarme pañales para bebés, os lo agradezco de antemano.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/C1C61AKVV7)
