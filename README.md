# LearningObjectiveSuggestions

ILIAS EventHook Plugin which calculates scores for learning objectives of a course. 
Based on the scores, learning objectives are suggested to a user for further learning.

## Installation
Start at your ILIAS root directory
```
mkdir -p Customizing/global/plugins/Services/EventHandling/EventHook  
cd Customizing/global/plugins/Services/EventHandling/EventHook
git clone https://git.studer-raimann.ch/ILIAS-Kunde-DHBW-Karlsruhe/LearningObjectiveSuggestions.git
```
As an ILIAS administrator, go to "Administration -> Plugins" and install/activate the plugin.

## Configuration
The plugin MUST be configured accordingly before the calculations are running correctly.

## Calculations
The calculation is started when a user is added to a course that is specified in the configuration and for which the calculation is enabled.
Or when a user has passed a course.