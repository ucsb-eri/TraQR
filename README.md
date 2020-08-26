# TraQR
This project originated during the Covid-19 pandemic in 2020.  After our UCSB campus had been essentially closed for several months during the initial statewide stay-at-home orders, campus was investigating options for limited re-opening of the UCSB campus to some limited research staff while at the same time allowing essential and critical staff to get work done.  The problem was coming up with a process or methodology regulating access to the various locations while at the same time providing some proximity information if contact tracing was required.  This translated to a means of quickly and easily logging users  access to individual rooms.  In essence tracking their ingress to and egress from campus facilities and buildings.  A campus app had been created to scan QR codes at the entrances and exits of various spaces on campus.  But the tools initial release had several shortcomings, the most major being:
* App could not be installed on older phones
* Data had to be harvested centrally to be distributed to end points that might need it
  * The data collection was manually intensive and prone to error
* UCSBNetID Required
  * No simple mechanism for handling contractors etc...

This project began as a quick proof of concept for generating custom QR codes for Individuals specific to Building and Room; something that we could manage locally for our Department/Building.  The project quickly ballooned into something that looked like it might be a viable solution to the problem.

The primary benefits of this system in its early stages are:
* Departments are able to generate their own QR codes quickly
* Many of the slightly older phones that could not download the campus app work with this
* No specific app required!
  * Newer phones recognize the QR code directly from the camera app
* Flexibility:
  * Basically just a web request, so users could also be provided the URL (via text or other communication channels) for storage in other locations.
  * Contractors could be sent a text to click once the arrived on location, do the same when leaving


# Installation/Configuration
Please See the [Wiki](wiki) for Installation.

You will need sudo privs or access to root account for a few items upfront after cloning the repo.
First order of business is to make sure that the webserver is able to write to a limited set of locations.

From command line:
   ```
   cd run
   make perms     # the Makefile uses apache:apache as the webserver perms
   ```
