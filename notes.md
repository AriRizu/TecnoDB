- **TODO**


- [x] 1 acura
- [x] 2 alfa romeo
- [x] 3 audi
- [x] 4 bmw
- [x] 5 bmw (motos)
- [x] Changan
- [x] Chery
- [x] Chevrolet
- [x] Chrysler
- [x] 10 Citroen
- [x] Daewoo
- [x] DAF
- [x] Daihatsu
- [x] dodge
- [x] 15 ducati
- [x] faw
- [x] fiat
- [x] ford
- [x] foton
- [x] 20 geely
- [x] gm
- [x] gmc
- [x] great wall
- [x] hafei
- [x] 25 Hino truck
- [x] honda
- [x] honda (motos)
- [x] hyundai
- [x] isuzu
- [x] 30 iveco
- [x] Jaguar
- [x] Jeep
- [x] JiangHuai
- [x] JiangLing
- [x] 35 Kawasaki
- [x] Kenworth
- [x] Kia
- [x] Kovemoto
- [x] KTM
- [x] 40 Lamborghini
- [x] Land rover
- [x] LDV
- [x] Leapmotor
- [x] lexus
- [x] 45 Lifan
- [x] Mahindra
- [x] MAN Trucks
- [x] Maxus
- [x] McLaren
- [x] 50 Mercedes
- [x] MG
- [x] Mini
- [x] Mitsubishi w/o years
- [x] Mustang
- [X] 55 Nissan
- [x] Opel
- [x] Peugeot
- [x] Piaggio
- [x] Porsche
- [x] 60 Qianjiang FROM HERE TO YAMAHA, THERE IS NO YEARS
- [x] Renault
- [x] Royal
- [x] SANY
- [x] Scania
- [x] 65 Ssangyong
- [x] Subaru
- [x] Suzuki
- [x] Suzuki (motos)
- [x] SWM
- [x] 70  SYM
- [X] Toyota 
- [x] Triumph
- [x] TVS
- [X] 75 VOGE
- [X] Volkswagen
- [x] Volvo
- [x] Volvo Trucks
- [X] Voyah
- [X] 80 yamaha



**TODO CODE**

[x] Make a notification for out-of-stock items
[x] Make filter descendent ascendant
[x] Make mobile based design

[ ] ADD QUICK ADD VEHICLES, ITEMS AND EQUIPOS TO TRABAJOS PAGE (to modal when adding a job)
[ ] ADD SYNCHRONIZATION TO TRABAJOS PAGE SO IT LINKS VEHICLES WITH EQUIPOS AND ITEMS WHEN A JOB IS ADDED
[ ] ADD A 'NOT PAID' Checkbox to trabajos, if checked, the profit and expenses will be put on hold (not taken into account)
[ ] ADD image column to table 'cortes'
[ ] Simple Client Management

    Create a new clientes table with fields like id, nombre, telefono, cvu, notas. Then, change the trabajos table to have a cliente_id foreign key instead of the separate cliente_ fields.

    Why: This allows you to track repeat customers. You can build a history for each client, see all the jobs you've done for them, and store their contact information in one organized place.
[ ] Global "Quick Search" Bar

    Add a single search bar in the main header (partials/header.php) that is always visible. This bar would search across multiple tables simultaneously: autos (marca, modelo), items (nombre, nombres_secundarios), and trabajos (cliente_patente, detalle).
[ ] "Start Job from Vehicle" Button

    On the vehicle details page, next to the "Edit" or "Delete" buttons, add a button like "Crear Trabajo para este Vehículo".
[ ] Dedicated "Knowledge Base" for Procedures

    The notas field in the auto_equipos table is a great start. To make it the central feature you want, you should enhance it. When viewing a vehicle's details, instead of just a simple text note for an associated piece of equipment, allow for a rich text editor (like TinyMCE or Quill.js).

    Why: Your core goal is to save procedures. A simple text field is limiting. A rich text editor would let you use bold text for warnings, create bulleted lists for steps, and even embed images or link to external videos. This would transform the simple note into a powerful, reusable guide for future jobs.

    Implementation Idea: You could enhance the auto_equipos table by adding a procedimiento column of type TEXT or LONGTEXT to store this detailed information separately from simple notes.


```
└── 📁TecnoDB
    └── 📁.vscode
        ├── settings.json
    └── 📁api
        └── 📁handlers
            ├── auto_handler.php
            ├── equipo_handler.php
            ├── item_handler.php
            ├── trabajo_handler.php
        ├── db_setup.php
        ├── router.php
    └── 📁assets
        └── 📁css
            ├── style.css
        └── 📁js
            ├── auto.js
            ├── equipo.js
            ├── item.js
            ├── main.js
            ├── stats.js
            ├── trabajo.js
    └── 📁pages
        ├── dashboard.php
        ├── equipos.php
        ├── items.php
        ├── landing.php
        ├── trabajos.php
        ├── vehicles.php
    └── 📁partials
        ├── footer.php
        ├── header.php
        ├── modals.php
    ├── add_cars.php
    ├── config.php
    ├── favicon.ico
    ├── index.php
    └── notes.md
```