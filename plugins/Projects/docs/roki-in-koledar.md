# Roki soglasodajalcev in koledar

## Trenutni prikaz

Zahteva za dopolnitev ima samostojen stolpec s povezavo **Rok: dd.mm.yyyy**, ki odpre prvi povezani dokument. Pretečen rok ima rdeče poudarjen prikaz in pojasnilo ob kazalcu. Pod nazivom soglasodajalca se prikažeta vrsta in rok zadnjega veljavnega dogodka druge vrste, če ima določen `date_due`. Nov zadnji dogodek brez roka odstrani prejšnji rok iz pregleda.

Dogodke je mogoče urediti prek gumba Uredi v zgodovini zadeve. Ohranjena sta identiteta dogodka in vrstni red, spremembe pa se zapišejo v projektni dnevnik. Vrste dogodka ni mogoče spremeniti tako, da bi postali povezani dogodki neveljavni.

## Preučena podpora koledarja

Vtičnik Calendar že ima:

- koledarske dogodke v tabeli `events`;
- osebni koledar (`calendar_id` je uporabnik) in prikaz skupnega koledarja podjetja;
- celodnevne dogodke, začetni in končni datum ter polje `reminder`;
- podporo CalDAV in ActiveSync, ki uporabljata shranjene koledarske dogodke.

Povezava s projektnimi zadevami je izvedena za osebni koledar. Pravice posameznega koledarskega dogodka dovoljujejo pregled samo uporabniku, katerega ID je `calendar_id`. Skupni koledar podjetja ni del te izvedbe.

## Izvedena povezava

Na strani zadeve je ob zadnjem dogodku z rokom na voljo izrecno dejanje **Dodaj rok v moj koledar**. Ustvari se celodnevni dogodek v osebnem koledarju trenutnega uporabnika, z nazivom projekta, soglasodajalcem, vrsto dogodka in povezavo nazaj na zadevo. **Odpri koledar** prikaže mesec roka; **Odstrani povezavo s koledarjem** izključi nadaljnji prenos in odstrani ta koledarski vnos.

Tabela `projects_authority_calendar_links` povezuje zadevo, uporabnika in koledarski dogodek. Enoličnost `(case_id, calendar_id)` prepreči podvajanje. Povezava hrani tudi ID izvornega dogodka; njen obstoj pomeni vključen prenos. Če trenutno ni roka, povezava ostane brez koledarskega vnosa in ob naslednjem roku znova ustvari vnos.

Pravila posodabljanja:

1. Ponovno dodajanje iste zadeve v isti koledar posodobi obstoječi vnos.
2. Urejanje zadnjega dogodka z rokom posodobi datum in opis koledarskega vnosa.
3. Nov zadnji dogodek z rokom zamenja prejšnji rok.
4. Nov zadnji dogodek brez roka, odstranitev roka ali razveljavitev sproži ponoven izračun iz veljavne zgodovine; zastareli samodejni vnos se umakne.
5. Dostop do projekta se preveri pri dodajanju in posodabljanju. Spremembe uporabnika, članstva in projekta sprožijo uskladitev. Pred prikazom koledarja se ponovno preverijo njegove povezave; ob izgubi dostopa se povezava in samodejni vnos odstranita.
6. Urejanje povezanega vnosa v koledarju odpre izvorno zadevo. Neodvisne spremembe in brisanje so zavrnjeni tudi na ravni modela; povezavo odstranimo v zadevi.

Dodajanje v koledar odgovorne osebe in skupni koledar podjetja sta naslednja koraka po uskladitvi pravic. Sam obstoj polja `reminder` še ne potrjuje delovanja vseh obvestil; pri izvedbi je treba preveriti dostavo oziroma sinhronizacijo na uporabljene odjemalce.

Povezavo vzdržuje poslušalec dogodkov vtičnika Projects; Calendar ponuja dogodka pred branjem in ob urejanju vnosa. Osnovni dogodki Calendar ostanejo nespremenjeni. Migracija dodaja samo povezovalno tabelo, brez spreminjanja obstoječih podatkov. Celodnevni rok uporablja obstoječe vključujoče shranjevanje končnega dne; CalDAV ga izvozi z izključujočim koncem naslednjega dne. Samodejno pošiljanje obvestil ni vključeno (`reminder = 0`).
