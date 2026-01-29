> [!NOTE]
> This script is meant to facilitate the creation of flight plans in [FSAirlines Virtual Airlines Management System](https://www.fsairlines.net/) by automatically providing "reasonable" random departure and arrival times in UTC based on the sunrise time at the departure airport.

<img width="666" height="788" alt="Screenshot_1" src="https://github.com/user-attachments/assets/49390d21-ae9e-4077-b8a2-8cc6aea706e7" />
<img width="666" height="1182" alt="Screenshot_2" src="https://github.com/user-attachments/assets/03e95ffd-3944-40d6-a991-97a632089447" />

**Advanced options:**  
<img width="582" height="719" alt="Advanced-options" src="https://github.com/user-attachments/assets/dd6c3d34-6f26-45b9-8672-6c0a59a83ccd" />

> [!WARNING]
># 1. REQUIREMENTS:
>
>**1. A PHP-enabled web server.** This should not be a problem, as most FSA airlines will host a website. Nevertheless, the script can also be run locally using XAMPP, WAMP, MAMP, or a similar PHP stack.
>
>**2. To update the 4th and 5th lines of the _index.php_ file to define your FSAirlines _API key_ and _Airline ID_**:
>
><img width="1192" height="215" alt="AirlineID-APIKey" src="https://github.com/user-attachments/assets/d163d7e6-c0c0-4d7b-85a0-044b227a9840" />

> [!TIP]
> In Windows, you can edit php files with the default [Notepad](https://apps.microsoft.com/detail/9msmlrh6lzf3?hl=en-GB&gl=ES) or, even better, with [Notepad++](https://notepad-plus-plus.org/).
> In macOS, you can use native [TextEdit](https://support.apple.com/en-gb/guide/textedit/welcome/mac) or the much more powerful [Sublime Text](https://www.sublimetext.com/).

> [!NOTE]
> You will find your **Airline ID in the Overview section** of your Airline:  
https://www.fsairlines.net/crewcenter/index.php?status=va
>
> <img width="510" height="719" alt="AirlineID" src="https://github.com/user-attachments/assets/25ede5bb-74df-401e-bbfd-a7b9d5027790" />

> [!NOTE]
> You can generate and find your API Key in **_Edit VA Settings → API Key Management_**:  
> https://www.fsairlines.net/crewcenter/index.php?status=va_api_key
> 
> <img width="650" height="583" alt="WebsiteAPIKey" src="https://github.com/user-attachments/assets/9968026c-a9be-4f3f-a174-2bc1497970d2" />
>
> <img width="730" height="563" alt="SiteKEY" src="https://github.com/user-attachments/assets/f45f737c-f9e4-4079-bcc6-55548a902e21" />

> [!TIP]
> **You will find more info about what an API Key can do here:**  
> https://wiki.fsairlines.net/index.php/XML-Interface-v2
***

# 2. The motivation behind FSAirlines Flight Scheduler

What I have always found most annoying when generating flight plans in FSA, is the calculation of departure and arrival times; especially when many new plans have to be entered in succession. First of all, one has to come up with varied departure times for each leg, which also need to be minimally realistic; most routes are not meant to be flown departing at 4 a.m. Secondly, unless one lives in Europe, calculating reasonable departure UTC times in distant time zones is not a straight forward process because you need to know the time difference between UTC time and the departure airport. There are many tools for that, but it is both irritating and time-consuming. 

Regarding the arrival time, although tools like SimBrief generate flight plans with precise departure and arrival times,  having to calculate each route individually when introducing large batch of flight plans into FSA is a slow and tedious process that greatly slows everything down. Moreover, the flight plans generated with SimBrief are not a panacea, since winds can significantly alter the duration of the flight, especially on long-haul flights. My experience tells me that even if the duration of a route has been precisely calculated with SimBrief, in future flights it is difficult to meet the estimated flight time with precision.

# 3. About the code

I can more or less read and modify code, but I am completely unable to write code from scratch. For this project, I have used the code provided by Anthropic Claude Sonnet 4.5 and ChatGPT 5.2 as a base.

# 4. Why FSAirlines Flight Scheduler exists as a Github repo?

This FSA scheduler has far exceeded all my expectations, allowing me to calculate  departure and arrival times extremely quickly and with simplicity, with an error margin—in my opinion—more than reasonable. That's why I thought that other FSA airline admins may benefit from this tool, so I decided to share the code via this Github repository.

# 5. The logic behind FSAirlines Flight Scheduler

## 5.1 Asking FSAirlines for the coordinates of the ICAO code

The scheduler **connects directly to FSAirlines** via your airline's API and airline ID to obtain the **coordinates** of the requested airport (ICAO code).

> [!NOTE]
> **ICAO codes used by MSFS and other simulators can be, at times, different from those used by FSA**. Although an airport in FSA may have several ICAO codes listed in its profile, only one can be active. For this reason, when an ICAO code introduced is different from that used in FSA (for example, UTTP or ZSJJ airports in MSFS 2024), the scheduler will not be able to find it via the FSA API. In such cases, an error message will be generated with a link that leads to the airpot's information page in FSA, where it will be possible to quickly see which is ICAO code in FSA is the equivalent to the one requested.
> 
> <img width="572" height="185" alt="ICAO-not-found" src="https://github.com/user-attachments/assets/5a10669d-5fcd-4b9e-afe7-6548f986ddc4" />
>
> <img width="604" height="407" alt="MSFS-ICAO-FSA" src="https://github.com/user-attachments/assets/2f23b46c-7616-427e-8a0b-89805b9011f7" />

## 5.2. Computing en route time

Using the coordinates obtained from FSA, the **direct distance** (great circle route) between origin and destination is **calculated**. After that, using the **speed, cruise altitude, and ascend/descend rate and speed** specified in the form, the **en route time is computed**. 

## 5.3 Default values

> [!IMPORTANT]
> **1. All the default values can be modified as per the user's needs or preferences**.
> 
> **2. Custom values defined by the user are permanent; that's it, the user can set custom defaults.**
>
> The user's preferences are stored in the "session" PHP directory of the server (typically, _/var/lib/php/session_ for most Linux servers).
>
> **The button "Reset all" restores the default preferences**.
>
> <img width="594" height="427" alt="ResetAll" src="https://github.com/user-attachments/assets/50cfc865-ca65-4112-bf69-c143fa28263e" />

### 5.3.1 Default speed

**By default, Mach 0.8 is used for cruise speed**, which I have considered to be a typical average. During **climbs and descents**, the cruise speed is reduced to **250 knots**.

For convenience, a **list of common commercial aircraft** has been added. The **cruise speeds** for each of the listed aircraft were obtained from **[Eurocontrol](https://learningzone.eurocontrol.int/ilp/customs/ATCPFDB/)**.

<img width="605" height="1069" alt="Aircraft-list" src="https://github.com/user-attachments/assets/39bb6baa-879c-435c-8d4e-51d189a0ab76" />   

### 5.3.2 Default altitudes

The **default altitudes are 35,000 feet for jet aircraft, and 24,000 feet for turbo propellers**.

### 5.3.3 Default climb/descent rates

**Default climb and descent rates are 1,800 feet per minute** for flights with speed indicated in Mach, and 800 feet per minute for flights with speed indicated in knots.

## 5.4 Buffer time

To make the calculated flight time by the scheduler more realistic, **the script adds a 30-minute buffer** to the originally time calculated to compensate for the time spent taxiing at departure and arrival, deviations due to SID and STARS (especially when the runway in use for departure or arrival is in the opposite direction to the route), and the increase in distance due to the use of airways; since flights are never flown in a straight line as calculated by the script. As with other values, users can set their preferences.

## 5.5 Use of sunrise as the referece for the departure time calculation

Coding a reasonable departure time is not as straight forward as it may seem. Let's say 07:00 local time is the chosen reference departure time. Even if the coordinates of the departure airport are known, it is essential to know the time zone used in those coordinates, which requires access to some kind of database. Besides, time zones are quite often illogical due to unnatural borders and political reasons. That's why I thought that sunrise would be a better choice. Of course, the exact time of sunrise varies throughout the year (and especially in high latitudes), so I took a "one-size-fits-all" approach and, **by default, the reference date is the Spring Equinox (20th March)**. On this date, the sun rises **roughly between 07:15 and 07:45 local time** worldwide. The **calculation of sunrise** is made thanks to the following **free API**:

https://sunrise-sunset.org/api

Once the sunrise time is known (around 07:30 as an average), **a random departure time** —rounded to five-minute increments— **is selected between two hours before sunrise and 15 hours after**. The intention is to have the flight's departure time fall within a "reasonable" time frame.

> [!NOTE]
> As with other fields, both the reference date and the valid hour range are modifiable by the user.

## 5.6 Main limitations of this scheduler

The calculation of en route time with these parameters is, a priori, less precise than with SimBrief or similar tools because **winds and the increase in distance due to the use of airways are not being taken in**. However, as a trade-off, the calculation is infinitely faster and simpler, and I considered the error margin more than reasonable.

## 5.7 Convenience features

Once the departure time has been calculated, if the generated time is not to your liking, you can use the "**Recalculate Schedule**" button to calculate another random departure time —with the corresponding new arrival time— using the same parameters as before.

The "**Next Leg**" button is one of my most beloved features, as it will use the current destination's ICAO code as departure for the next flight plan, making the calculation of the following route even faster and simpler.

Finally, once the calculation is performed, **the ICAO codes of origin and destination can be copied by simply clicking on them with the mouse,** which simplifies the copying and pasting process. **The same applies to departure and arrival times**, which, in addition, are copied without the colon separator. That is, clicking on 10:45 copies 1045 to meet the format required for pasting into FSA.

<img width="553" height="476" alt="Conveniences" src="https://github.com/user-attachments/assets/f0f841bc-3e6e-481f-8cb5-20bbd3e7e97c" />

> [!TIP]
> I hope other FSA airline admins will find this script useful. Do feel free to use and modify it as desired. I just ask to be credited. Enjoy!

# 6. Credits

Flags by [Flags Icons](https://github.com/lipis/flag-icons)
