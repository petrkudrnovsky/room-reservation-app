### User
- Index
  - pouze superadmin
- Show
  - pouze superadmin + uživatel sám sebe
- Create
  - pouze superadmin
  - ostatní pouze při registraci (omezený formulář)
- Edit
  - pouze superadmin + uživatel sám sebe
  - možnost upravovat membera/admina místností/skupin - pouze superadmin
  - možnost zapnout/vypnout superadmina - pouze superadmin 
- Delete
  - pouze superadmin

### Group
- Index
  - group manager - skupiny, kterých je admin
  - member - skupiny, kterých je member
- Show
  - pouze admini a členové skupiny (ti se tam dostanou linkem ze show uživatele)
- Create
  - pouze superadmin
- Edit
  - pouze administrátor skupiny
      - administrátory skupiny řeší jenom superadmin 
- Delete
  - pouze superadmin

### Room
- Index
  - room manager - místnosti, kterých je admin
  - room member - místnosti, kterých je member
  - group manager a group member - místnosti, které patří jejich skupinám
  - veřejné místnosti úplně všichni
- Show
  - room manager i member místnost
  - group manager i member skupiny, které tato místnost patří
- Create
  - pouze superadmin
- Edit
  - room manager - kromě dalších managerů místnosti
  - group manager skupiny, které tato místnost patří - vše
- Delete
  - pouze superadmin

### Building
- Index
  - všichni authentifikovaní

### Reservation
- Index
  - všechny rezervace (speciální stránka na přehled) - superadmin
  - k místnosti
      - potvrzené rezervace - všichni, kdo mohou vidět detail místnosti (zobrazení je omezené pro uživatele, kteří nemají plný přístup k místnosti - správci místnosti, skupiny)
      - čekající na potvrzení - pouze správci místnosti, skupiny, superadmin
          - stejní uživatelé mohou schvalovat/odmítat
- Show
  - jednodušší zobrazení (obsazenost - začátek, konec, detaily místnosti) - všichni, kdo mohou vidět detail místnosti
  - full zobrazení
      - room&group admin, reservedFor, (návštevník)
- Create
  - uživatel místnosti a člen skupiny (bez editu reservedFor)
  - room manager a group manager (s editem reservedFor)
- Edit
  - reservedFor (bez editu reservedFor)
  - room manager a group manager (s editem reservedFor)
- Delete
  - reservedFor, room&group manager
- Approve/Reject
  - room manager, group manager