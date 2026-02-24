> [!TIP]
> If you would like to provide any kind of **feedback**, please do so in the following **post in the forum**, thanks:  
> [https://forum.fsairlines.net/viewtopic.php?t=10424](https://forum.fsairlines.net/viewtopic.php?t=10424)

For details, please check the following sections in the Wiki:

[0. Motivation for FSAirlines Flight Scheduler](https://github.com/rcutanda/FSAirlines-Flight-Scheduler/wiki/0.-Motivation-for-FSAirlines-Flight-Scheduler)  
[1. REQUIREMENTS and installation](https://github.com/rcutanda/FSAirlines-Flight-Scheduler/wiki/1.-REQUIREMENTS-and-installation)  
[2. How the scheduler works](https://github.com/rcutanda/FSAirlines-Flight-Scheduler/wiki/2.-How-the-scheduler-works#234-ncepncar-reanalysis-1-global-atmospheric-dataset)  
[3. Convenience features](https://github.com/rcutanda/FSAirlines-Flight-Scheduler/wiki/3.-Convenience-features)  
[4. What to expect from FSA Flight Scheduler regarding accuracy](https://github.com/rcutanda/FSAirlines-Flight-Scheduler/wiki/4.-What-to-expect-from-FSA-Flight-Scheduler-regarding-accuracy)  
[5. Credits](https://github.com/rcutanda/FSAirlines-Flight-Scheduler/wiki/5.-Credits)  

This script is meant to **facilitate the creation of flight plans in [FSAirlines Virtual Airlines Management System](https://www.fsairlines.net/)** by automatically providing **"reasonable" random departure times within a range of local times** selected by the user. The duration of the flight is then calculated and, finally, **departure and arrival times are provided in UTC**, as required by FSA.

There are **two modes** of scheduling available: **charter**, for single, individual, isolated flights, and **daily schedule**, where an airplane is meant to start early in the day and concatenate a series of flights until its end of that day while respecting a minimal turnover time for refuelling, disembarking and boarding. 

Because **FSA Flight Scheduler is designed to generate all-year-round valid schedules**, it does not make use of real-time weather for planning. It does use, nevertheless, a **simplified version of the [NCEP/NCAR Reanalysis 1 database](https://cliks.apcc21.org/dataset/ncep) with average winds from 1958 to 2008**. This database is used from FL 240 up to FL 450 and, with all its limitations, mitigates up to a reasonable point the enormous influence that jet streams have in the timing of routes (particularly long ones) affected by these streams.

FSA Flight Scheduler also includes a **ready-to-use list of common aircrafts with unique performance profiles** for each of the **main phases of flight**. Prociles include **speed** (in TAS and MACH, depending on the altitude) and **rate of climb/descent**. All these profiles have been gathered from **[Eurocontrol Aircraft Performance Database](https://learningzone.eurocontrol.int/ilp/customs/ATCPFDB/default.aspx?)**, and FSA Flight Scheduler includes a dedicated section to **automatically download and install new profiles**. Of course, each profile can be edited or deleted. **Manual generation of profiles** is also possible when necessary by manually filling in as many as the available fields as possible.

The combination of weather and aircraft profiles provides FSAirlines Flight Scheduler with a **decent level of accuracy in timings** when compared against the average time taken by real-life routes:

**Mean error** (scheduler − real): -1.0 min (near-zero overall bias).  
**Mean absolute error** (MAE): 10.2 min.  
**Median absolute error**: 7 min.  

* 38% of routes within ±5 min  
* 64% within ±10 min  
* 77% within ±15 min  
* 85% within ±20 min  
* 96% within ±30 min  

Check [4. What to expect from FSA Flight Scheduler regarding accuracy](https://github.com/rcutanda/FSAirlines-Flight-Scheduler/wiki/4.-What-to-expect-from-FSA-Flight-Scheduler-regarding-accuracy) for more details regarding accuracy. 

Because this tool has far exceeded all the expectations that I had before start writing a single line of code (both in terms of convenience and precision), I decided to create this repository and share its code under a [MIT license](https://github.com/rcutanda/FSAirlines-Flight-Scheduler/blob/main/LICENSE), so anyone is free to use and modify this tool. 

<img alt="FSA Flight Scheduler screenshot" src="https://github.com/user-attachments/assets/4a165481-861a-425d-875f-c6c23faf99df" />

<img alt="Error distribution" src="https://github.com/user-attachments/assets/e16f7e85-e106-429d-883c-6c93bb2a4133" />
