# Directory Structure Standard

**CRITICAL PRINCIPLE**: Test directory structure must EXACTLY mirror the source directory structure at ALL testing levels.

## App-to-Test Mapping
```
app/{Directory}/{ClassName}.php
↓
tests/Unit/{Directory}/{ClassName}Test.php
tests/Integration/{Directory}/{ClassName}IntegrationTest.php
```

## Database-to-Test Mapping
```
database/factories/{Directory}/{ClassName}Factory.php
↓
tests/Unit/Database/Factories/{Directory}/{ClassName}FactoryTest.php
```

## Examples
```
app/Rules/Events/DateCanBeChanged.php
→ tests/Unit/Rules/Events/DateCanBeChangedUnitTest.php
→ tests/Integration/Rules/Events/DateCanBeChangedIntegrationTest.php

app/Models/Wrestlers/Wrestler.php
→ tests/Unit/Models/Wrestlers/WrestlerTest.php

app/Actions/Wrestlers/EmployAction.php
→ tests/Integration/Actions/Wrestlers/EmployActionTest.php

database/factories/Wrestlers/WrestlerFactory.php
→ tests/Unit/Database/Factories/Wrestlers/WrestlerFactoryTest.php

database/factories/Events/EventFactory.php
→ tests/Unit/Database/Factories/Events/EventFactoryTest.php
```
