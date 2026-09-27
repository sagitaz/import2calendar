# import2calendar plugin

This plugin is used to import a calendar in ICal format into the official Jeedom Calendar plugin; therefore, the latter must be installed and configured.
## Prerequisites
- Calendar Plugin
- import2calendar plugin

## Warning
**You cannot make any changes to the iCal file; the plugin retrieves information from the iCal file and sends it to the Jeedom Calendar plugin. Do not make any changes to the calendar created in the Calendar plugin, as they will be deleted the next time you update your iCal file.**


# <u>Configuration</u>

## Create a device
Start by adding a device and choosing a name for it
### Import settings
- **ical**: Specify the URL of the ical file to be converted.
- **Forced start time**: Select a start time for all events in the calendar. By default, this will be the start time of the event saved in the iCal file.
- **Forced end time**: Select an end time for all events in the calendar. By default, this will be the end times of the events stored in the iCal file.
- **iCal Auto**: French holidays and public holidays. If selected, leave the iCal field blank.
- **cron**: Select the desired refresh interval for the calendar.

### Display Settings
- **icon**: the icon that will be applied to each event.
- **Background color**: Default background color for each event.
- **text color**: default text color for each event.

### Customizing Events
Here you can choose to customize certain events in your iCal.

- Background color and text color.

By default, nothing is changed here; these options allow you to modify the start and end times so that, for example, you can schedule actions in advance.
- Start time: The event will begin X hours earlier.
- End time: The event will end X hours later.

### Start and End Actions
The actions defined here will be added to all events on your calendar.
You can rearrange actions using drag-and-drop.


![Action Settings](../images/import2calendar_screenshot03.png)

In the **name** field, you can specify the event for which the action is intended.
- Accepts a partial name
- Ignores capital letters

**1** and **2** - Leave blank or enter **all** to have the action added to all calendar events.
**4** - Select **others** so that the action is added to all calendar events except those for which a custom action is scheduled.
**3** and **5** - Enter the **event name** so that the action is added only for those events.


You can now click **Save**.
The corresponding calendar entry will be created in the calendar plugin.

Example:

Here, we can see the events in the Calendar plugin; we can also see that I’ve customized the color as well.

![Calendar](../images/Agenda-exemple.png)

Configuration in the import2calendar plugin

![Colors](../images/personnalisation-couleurs.png)

![Actions](../images/personnalisation-actions.png)

Let's go back to the calendar to check the actions
![Inspection Schedule](../images/import2calendarActions.gif)

## Editing a device
If you change any of the following options:
- icon
- background color
- text color
- Actions de début
- final actions

Events will be updated in the calendar

## Event Management
Every time a backup is performed or every time the configured cron job parses the iCal file, if an event is no longer in the iCal file, it is removed from the calendar.
Events that took place more than 3 days ago are not imported and will be deleted over time.

## Instances
The rules defined in your iCal file are converted to the Jeedom Agenda format. I haven't tested all the possibilities, so if any of them don't work, please attach the relevant line from the import2calendar log: **event options** (set your logs to "warning" or "debug" mode).


Events in the occurrence remain visible on the calendar as long as the occurrence is valid.

Example: 1 event every 5 days from March 1, 2024, to November 24, 2024. All events are visible on the calendar until November 27, 2024 (3 days after the end of the occurrence).

## Calendar Plugin
Information about upcoming events will be added to your calendar.
You will therefore have 9 new commands:
- yesterday
- today
- tomorrow
- the day after tomorrow
- Day 3
- Day 4
- Day 5
- Day 6
- Day 7

## ical <-> Jeedom
In some cases, it won't be possible to convert to the Jeedom format, so you'll need to adjust your schedules.
This is the case, for example, with this:
- Tuesdays and Wednesdays every 3 weeks
To have this data reported to Jeedom, you need to create:
- Tuesday every 3 weeks
- Wednesday every 3 weeks

# <u>JeeMate</u>
- The description and location will be visible in the calendar imported into JeeMate.

# <u>Warning</u>
- The name of the created schedule is the same as that of the device + "-ical" (it can now be edited   )
- The room will be identical

# <u>Support</u>
- Jeedom Community
- Discord JeeMate

# <u>Request for Help</u>
To make it easier for me to debug a conversion error, I'd like to ask you to create a test calendar with only the event that's causing the problem and to give me access to that iCal.


# <u>Thank you</u>
The plugin and support are free, but if you'd like to buy me a coffee or some diapers, I thank you in advance.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/C1C61AKVV7)
