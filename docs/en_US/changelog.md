# Changelog for the import2calendar plugin

>**IMPORTANT**
If there is no information about the update, it means that the update only involves documentation, translations, or text.

# September 27, 2026 Beta 1.5.0
- Start and end times for custom events can be set to the minute (0 to 360 min)
- Calendar updates use far fewer resources, especially for large calendars
- An error in one calendar no longer prevents subsequent calendars from updating
- More specific error messages in the logs
- Fixes notes and locations that are lost when they contain a comma or semicolon
- Fixed monthly recurring events of the "2nd Monday" or "last Friday" types, which were missing from the commands for the upcoming days
- Fixed an issue where the import would stop for certain recurring events
- Fixed an issue where old events without an end date were incorrectly retained
- Fixed an issue where time zones were incorrectly recognized (Australia, South America, Mexico, etc.), which prevented imports or caused schedule discrepancies
- An unknown time zone no longer interrupts the import: Jeedom's time zone is used instead
- Fixed an issue where a calendar entry was created in duplicate every time the Jeedom system was updated; a message now alerts users to delete the duplicates

# February 25, 2026 Beta 1.4.8
- Bug fix related to date validation

# February 5, 2026 Beta 1.4.7
- Permission to change the calendar name

# 10/04/2025 Stable 1.4.2
- Fixed an error involving double quotes in the event name

# June 16, 2025 beta 1.4.1
- Bug fix for Cron

# May 23, 2025 Stable 1.4.0
- Jeedom version 4.4 or higher
- Debian version 11 or higher

# May 7, 2025 Beta 1.3.6
- Download the iCal file before processing

# April 25, 2025 Beta 1.3.5
- Adding a timeout for requests
- Added a retry mechanism for requests
- Booking Calendar Correction

# April 9, 2025 Beta 1.3.4
- Minor corrections

# April 5, 2025 Beta 1.3.3
- Bug fix for ical2calendar regarding events from day -1 to day +7

# March 6, 2025 Beta 1.2.8
- Bug fix for ical2calendar
- Added commands for day -1 as well as days 2 through 7
- Process occurrences that repeat on a specific day rather than on a specific date

# February 21, 2025 Beta 1.2.7
- Time zone correction
- Add "Refresh" command

# February 21, 2025 Beta 1.2.6
- Correction: "exclude date" and "include date"
- Fix cmd today if the event occurs every 2 weeks

# February 11, 2025 Beta 1.2.5
- Creating "today" and "tomorrow" commands in the Calendar plugin for your iCal events.
- Correction of event dates
- Other minor corrections

# February 3, 2025 Beta & Stable 1.2.0
- Correction if the time zone is formatted incorrectly

# January 30, 2025 Beta 1.1.9
- Fix for a weekly recurring event from an Infomaniak calendar

# January 24, 2025 Stable 1.1.8
- Correction of **others** in actions

# January 9, 2025 Beta 1.1.7
- Fixed calendar update with excluded dates

# January 7, 2024 Beta 1.1.6
- Adjusting the end time for the entire day

# November 6, 2024 Stable 1.1.5
- Addition of school holidays for French overseas departments and territories

# 10/31/2024 Stable 1.1.4
- Frequency correction (see documentation)

# 10/21/2024 Stable 1.1.3
- Correction: If the event includes an alarm, the title and description were modified.
- Fixed the import of webcal links.

# 10/16/2024 Stable 1.1.2
- Fix for retrieving events spanning multiple years

# 10/07/2024 Stable 1.1.1
- Correct if there is a comma in the event name

# October 3, 2024 Stable 1.1.0
- Correction regarding the deletion or relocation of an event within a recurring schedule

# October 1, 2024 Beta 1.0.9
- PHP warning fixes
- Added plugin version number
- Correction to the event update

# August 17, 2024 Stable 1.0.8
- Translation into English, German, Spanish, Italian, and Portuguese. Thanks, @mips

# May 6, 2024 Stable 1.0.7
- Fix the setTime error.

# May 6, 2024 Beta 1.0.6
- Added the ability to force the start and end times of an event.

# May 1, 2024 Beta 1.0.5
- Taking Excluded Dates into Account in Recurring Events
- Taking Modified Dates into Account in Recurring Events

# April 29, 2024 Stable 1.0.0
- Converting emojis to HTML (visible in the event name and description (JeeMate v3))
- Conversion of time zones to the Windows format (Romance Standard Time style)
- Added options for actions (all, others, event)
- Location Awareness (available in JeeMate v3)
- Option to set a custom start and end time.

# April 25, 2024 Beta 0.8.0
- Removing emojis from the event name (MySQL error 22007)

# April 25, 2024 Stable 0.7.0
- Try it out and post your feedback on the Jeedom documentation

# April 1, 2024 Beta 0.6.0
- Added "Documentation" and "Changelog" buttons
- Added a button to Discord (on the JeeMate Discord server, 1 dedicated channel)

# March 30, 2024 Stable 0.5.0
- First stable release

# March 29, 2024 Beta 0.5.0
- color correction for text input in dark mode + minor visual tweak
- custom color, case-insensitive
- Events with occurrences: if the last event in the series occurred more than 3 days ago, the series is not displayed.

# March 28, 2024 Beta 0.4.0
- Time zone correction
- Incorporation of descriptions (visible in JeeMate)
- Scheduling Recurring Tasks
- Managing the end of a recurring schedule, by date or by number of repetitions
- Specific color management for certain events

# March 12, 2024 Beta 0.3.0
- Fix for Jeedom 4.3 compatibility
- Default text and background colors

# March 11, 2024 Beta 0.1.0
- first beta version
