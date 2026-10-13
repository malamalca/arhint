# Načrt izvedbe: pogoji in mnenja na projektih

> Poenostavitev po navodilih uporabnika: dogodek nima faze ali različice dokumentacije; ni nastavitev potrebnosti, potrditve seznama ali opombe pregleda. Dokumenti se izbirajo v enem polju. Tabela kaže tri stolpce s kljukicami in datumom prvega povezanega dokumenta; dodajanje soglasodajalca je v meniju Vpis. Spodnji prvotni načrt ostaja zgodovina predloga; veljavna navodila so v `pogoji-in-mnenja-uporaba.md`.


Datum: 13. 10. 2026  
Status: prva faza implementirana; nadaljnje faze so načrtovane

## 1. Namen in odločitev

Ročno tabelo soglasodajalcev v opisu projekta nadomestimo s strukturirano evidenco postopkov v vtičniku **Projects**, dostopno prek novega zavihka **Pogoji in mnenja**.

Evidenca mora odgovoriti na vprašanja:

- Kateri soglasodajalci so potrebni za projekt?
- Kaj smo komu oddali in s katero različico dokumentacije?
- Ali čakamo odgovor ali moramo pripraviti dopolnitev?
- Katera mnenja še manjkajo?
- Ali so mnenja zbrana in ali je odgovorna oseba potrdila pripravljenost za oddajo na upravno enoto?

Za začetni obseg ne uvajamo samostojnega vtičnika. Podatkovni model in pravila postopka naj bodo ločeni znotraj Projects, da jih lahko pozneje izločimo, če bodo podpirali tudi druge vrste upravnih postopkov.

Dokumenti in njihove priloge ostanejo v vtičniku **Documents**. Nova evidenca hrani povezave na obstoječe dokumente, ne kopij datotek. Naloge in mejniki dopolnjujejo postopek, niso pa njegova osnovna evidenca.

## 2. Obstoječe izhodišče

- `src/Model/Table/ProjectsTable.php`: projekt, sodelavci in mejniki.
- `templates/Projects/view.php`: podrobnosti projekta z zavihki.
- `src/Model/Table/ProjectsMilestonesTable.php`: mejniki z roki in projektnimi nalogami.
- `src/Event/ProjectsEvents.php`: povezovanje dokumentov s projektom in zapis v dnevnik.
- `../Documents/src/Model/Table/DocumentsTable.php`: dokumenti že podpirajo `project_id`.
- `src/Policy/ProjectAccessTrait.php`: obstoječa osnova preverjanja dostopa do projekta.

Pri izvedbi se uporabijo obstoječi vzorci obrazcev, prevodov, dnevnika in avtorizacije. Pred ponovno uporabo pravil dostopa preverimo njihovo pravilnost, predvsem ločevanje podjetij in članstvo na projektu.

## 3. Postopek uporabnika

### 3.1 Priprava seznama

Uporabnik na projektu doda potrebne soglasodajalce in za vsakega določi:

- ali so potrebni projektni pogoji;
- ali je potrebno mnenje;
- odgovorno osebo v ekipi;
- kontakt in opombo.

Za vsako fazo so možne vrednosti **ni določeno**, **potrebno** in **ni potrebno**. Zadnja zahteva razlog. Prazno polje ne pomeni, da faza ni potrebna. Predloge seznamov se dodajo pozneje; uporabnik vedno potrdi seznam za konkretni projekt.

### 3.2 Vloga za projektne pogoje — DPP

Z dejanjem **Evidentiraj oddajo** uporabnik vnese datum oddaje, poveže dokument vloge ter označi dokumentacijo oziroma njeno različico. Po potrebi poveže dokazilo o oddaji, vpiše številko zadeve in datum naslednjega preverjanja.

Oddaja spremeni stanje te faze v **Čakamo odgovor**. Evidentiranje oddaje samo po sebi ne pošilja vloge soglasodajalcu.

### 3.3 Prejem odgovora in dopolnitve

Z dejanjem **Evidentiraj odgovor** uporabnik izbere projektne pogoje, mnenje, zahtevo za dopolnitev ali drug odgovor. Vnese datum prejema, poveže dokument in zapiše povzetek oziroma naslednji korak.

Zahteva za dopolnitev pomeni **Potrebna dopolnitev**. Oddaja dopolnitve je nov dogodek, povezan z zahtevo, po katerem ponovno čakamo odgovor. Dovoljenih je več zaporednih dopolnitev. Drug odgovor ne zaključi faze samodejno.

### 3.4 Vloga za mnenje — DGD

Ko je pripravljena dokumentacija DGD, uporabnik evidentira vlogo za mnenje pri posameznem soglasodajalcu. Fazi DPP in DGD imata ločeno stanje in povezano zgodovino.

Mnenje, pridobljeno že v fazi DPP, se lahko uporabi tudi za DGD, vendar mora odgovorna oseba potrditi njegovo ustreznost za trenutno različico dokumentacije. Sam obstoj dokumenta ne pomeni, da je zahteva izpolnjena.

### 3.5 Zaključek

Sistem prikaže **Mnenja zbrana**, ko so vse zahteve za mnenja izpolnjene ali izrecno označene kot nepotrebne, ni nerešenih zahtev za dopolnitev in je seznam soglasodajalcev potrjen kot popoln.

Oznako **Pripravljeno za oddajo na UE** ločeno potrdi odgovorna oseba po pregledu celotne dokumentacije. To je interna potrditev in ne avtomatična presoja zakonske popolnosti vloge.

Ob spremembi dokumentacije ali zahtev, ki vpliva na že potrjena mnenja, sistem zahteva ponovni pregled njihove ustreznosti in umakne aktivno potrditev pripravljenosti. Zgodovino potrditev ohrani.

## 4. Uporabniški vmesnik

### Pregled na projektu

Na vrhu zavihka:

- število ustreznih mnenj glede na število zahtevanih;
- seznam manjkajočih mnenj in odprtih dopolnitev;
- opozorilo, če seznam ali zahteve še niso določeni;
- stanje pripravljenosti in podatek o zadnji potrditvi.

Tabela:

| Soglasodajalec | Projektni pogoji | Mnenje | Naslednji korak | Odgovorna oseba |
| --- | --- | --- | --- | --- |
| Elektro | Prejeto · datum in povezava | Čakamo odgovor · datum oddaje | Preveriti odgovor | Član ekipe |
| DRSV | Prejeto · datum in povezava | Potrebna dopolnitev | Oddati dopolnitev do roka | Član ekipe |

Klik na vrstico odpre podrobnosti in časovnico vseh oddaj, odgovorov, dopolnitev in potrditev. Prikaz mora uporabljati besedilne oznake; barva je dodatna pomoč.

Osnovna dejanja: **Dodaj soglasodajalca**, **Evidentiraj oddajo**, **Evidentiraj odgovor**, **Oddana dopolnitev**, **Potrdi ustreznost mnenja** in **Potrdi pripravljenost za UE**.

Pri povezovanju dokumenta se ponudi izbor obstoječih dokumentov projekta. Datum izdaje dokumenta, datum prejema in datum oddaje so ločeni podatki.

### Skupni pregled — poznejša faza

Pregled prek dostopnih projektov s filtri: odgovorna oseba, čakamo soglasodajalca, potrebna dopolnitev, rok preverjanja in manjkajoča mnenja.

## 5. Predlagani podatkovni model

Imena so delovni predlog; pred migracijami jih uskladimo s konvencijami repozitorija.

| Evidenca | Predlagana tabela | Vsebina |
| --- | --- | --- |
| Soglasodajalci | obstoječa tabela `contacts` | Podjetja (`kind = C`) iz CRM; izbor prek obstoječega autocomplete, brez ločenega imenika |
| Soglasodajalec projekta | `projects_authority_cases` | Projekt, soglasodajalec, zahteve obeh faz, razlogi izjem, odgovorna oseba, opombe in referenca zadeve |
| Dogodki postopka | `projects_authority_events` | Zadeva, faza, vrsta dogodka, datum dogodka, datum vnosa, avtor, povezava na predhodni dogodek, različica dokumentacije, povzetek in rok |
| Dokumenti dogodka | `projects_authority_event_documents` | Dogodek, dokument in njegova vloga: vloga, odgovor, dokazilo ali dokumentacija |
| Potrditve projekta | `projects_authority_reviews` | Projekt, vrsta potrditve, avtor, čas, opomba in podatek o razveljavitvi potrditve |

Ločena povezovalna tabela za dokumente omogoča več dokumentov na dogodek ter uporabo istega dokumenta pri več povezanih dogodkih.

Vsaka zadeva pripada projektu in se prek `contact_id` poveže s podjetjem iz Kontaktov istega lastnika. Avtor dogodka je ločen od odgovorne osebe. Na projektu omogočimo več zadev istega soglasodajalca, če gre za različna področja ali postopke; uporabniku jih jasno poimenujemo.

Vrste dogodkov vključujejo oddajo vloge, prejem pogojev, prejem mnenja, zahtevo za dopolnitev, oddajo dopolnitve, drug odgovor ter potrditev oziroma preklic ustreznosti mnenja.

Trenutna stanja se izračunajo iz veljavnih dogodkov in zahtev. Pravila izračuna naj bodo v eni namenski storitvi, ne razpršena po kontrolerjih in predlogah. Morebitni shranjeni povzetki so obnovljiv predpomnilnik, ne vir resnice.

## 6. Pravila stanj in integriteta

| Stanje faze | Pomen |
| --- | --- |
| Ni določeno | Potrebnost faze še ni potrjena |
| Ni potrebno | Izrecna odločitev z razlogom |
| Ni oddano | Faza je potrebna, oddaja še ni evidentirana |
| Čakamo odgovor | Oddana vloga ali dopolnitev brez zaključenega odgovora |
| Potrebna dopolnitev | Obstaja nerešena zahteva za dopolnitev |
| Prejeto / v pregledu | Dokument je prejet, ustreznost še ni potrjena |
| Zaključeno | Zahteva je izpolnjena in potrjena za relevantno dokumentacijo |
| Potreben ponovni pregled | Sprememba je vplivala na prej potrjeno ustreznost |

Pravila:

- Poznejši datum sam po sebi ne razreši starejše zahteve; odgovor ali dopolnitev povežemo z ustrezno oddajo oziroma zahtevo.
- Uvoz starega mnenja brez evidence vloge je dovoljen in jasno označen kot zgodovinski podatek.
- Mnenje, ki ne omogoča zaključka, ne šteje med ustrezna mnenja; uporabnik določi potreben nadaljnji korak.
- Spremembe za nazaj sprožijo ponoven izračun. Popravki in razveljavitve imajo sled avtorja, časa in razloga.
- Arhiviranje soglasodajalca v imeniku ohrani zgodovino projektov.
- Brisanje ali premik povezanega dokumenta ne sme tiho pustiti izpolnjene zahteve brez dokazila; povezave zaščitimo oziroma sprožimo ponovni pregled.
- Povezani projekt, dokument, soglasodajalec in odgovorna oseba morajo biti iz dovoljenega obsega istega podjetja. Za odgovorno osebo preverimo tudi dostop do projekta.
- Rok preverjanja je operativni podatek. V prvi različici ne izračunavamo zakonskih rokov ali posledic molka organa.

## 7. Povezava z obstoječimi funkcijami

### Dokumenti

Dokument ostane na obstoječem naslovu v Documents. Ob povezovanju preverimo dostop uporabnika in pripadnost projektu. Dokumenta ne premaknemo na drug projekt brez izrecnega uporabniškega dejanja. Samodejni dnevniški zapis ob dodelitvi dokumenta projektu še ne nadomesti dogodka postopka.

### Mejniki in naloge

Za celoten projekt predlagamo mejnike **Pogoji zbrani**, **Mnenja zbrana** in **Oddaja na UE**. Naloge v Projects uporabljamo za pripravo dopolnitev in druga konkretna opravila. Zaključena naloga sama po sebi ne pomeni oddane dopolnitve; oddaja mora biti evidentirana.

### Dnevnik in pravice

Pomembna dejanja se zapišejo v obstoječi projektni dnevnik s povezavo na zadevo. Natančna zgodovina ostane v dogodkih. Branje in spreminjanje uporabljata projektne pravice ter dodatno preverjanje dostopa do dokumentov. Pravico potrjevanja pripravljenosti določimo izrecno in jo preverjamo na strežniku.

## 8. Faze izvedbe

### Faza 0 — uskladitev pravil in tehnična priprava

1. Potrditi poimenovanje vmesnika: stolpec **Mnenje**; pri zgodovinskih dokumentih ohraniti dejansko vrsto, tudi soglasje.
2. Določiti, kdo lahko potrdi ustreznost mnenja, popolnost seznama in pripravljenost za UE.
3. Preveriti primere več dopolnitev, mnenja iz DPP ter sprememb dokumentacije.
4. Preveriti povezljivost imenika s CRM in obstoječe vzorce pravic, migracij ter testov.

**Rezultat:** potrjena pravila in model, pripravljen za implementacijo.

### Faza 1 — osnovna uporabna različica

1. Dodati migracije, modele, povezave, validacijo in politike dostopa.
2. Implementirati storitev izračuna stanj in pripravljenosti.
3. Povezati podjetja iz Kontaktov prek autocomplete in dodati seznam soglasodajalcev projekta.
4. Dodati obrazce za oddaje, odgovore, dopolnitve in povezovanje dokumentov.
5. Dodati zavihek, časovnico, povzetek manjkajočih mnenj ter potrditve.
6. Dodati sled sprememb in projektni dnevnik.
7. Preveriti ključne scenarije s testi in pregledom vmesnika.

**Rezultat:** nov projekt lahko vodimo od prve vloge do interne potrditve pripravljenosti za UE brez ročne Markdown tabele.

### Faza 2 — prenos obstoječih evidenc

1. Iz tabel pripraviti predogled soglasodajalcev, datumov in dokumentnih povezav.
2. Preveriti obstoj, dostopnost in projektno pripadnost dokumentov.
3. Zahtevati potrditev pomena `-`, vrste dokumentov ter zahtev posamezne faze.
4. Prazne celice ohraniti kot neznano stanje; iz njih ne ustvarjati oddaj ali čakanja.
5. Datume iz povezav prenesti kot zgodovinske datume z možnostjo popravka; ne domnevati, da so datum prejema.
6. Preprečiti podvojitve ob ponovljenem prenosu in ohraniti izvirni zapis do preveritve.

**Rezultat:** obstoječi projekti imajo pregledano strukturirano evidenco. Za majhno število projektov zadostuje ročni prenos prek novih obrazcev; množični uvoz dodamo glede na dejanski obseg.

### Faza 3 — organizacija dela

1. Dodati naloge za dopolnitve z odgovorno osebo in rokom.
2. Dodati skupni pregled odprtih zadev prek dostopnih projektov.
3. Dodati predloge seznamov soglasodajalcev.
4. Dodati opomnike z nastavljivim datumom preverjanja in brez podvajanja obvestil.
5. Povezati povzetke z mejniki po vnaprej potrjenih pravilih.

**Rezultat:** uporabnik vidi, katera dejanja mora opraviti in kje čaka zunanji odgovor.

### Faza 4 — avtomatizacija po potrjeni uporabi

Možne razširitve: priprava vlog iz predlog, predlog razvrstitve prejetih dokumentov, izvoz evidence in povezava s komunikacijo. Razvrstitve dokumentov in njihove ustreznosti uporabnik potrdi. Pošiljanje vlog je ločena funkcija z jasnim dejanjem uporabnika.

## 9. Merila sprejema in preverjanje

Prva različica je zaključena, ko:

- uporabnik za projekt določi zahtevane faze in vidi neodločene zahteve;
- oddaja vloge povzroči pravilno stanje čakanja;
- več zaporednih dopolnitev ohrani zgodovino in pravilno odprta opravila;
- DPP in DGD vodita ločena stanja;
- prejeto, a nepotrjeno ali neustrezno mnenje ne šteje med zaključene zahteve;
- zgodnje mnenje šteje za DGD šele po potrditvi ustreznosti;
- oznaka »ni potrebno« zahteva razlog;
- povzetek navede konkretna manjkajoča mnenja in ovire;
- brez potrjenega seznama ni mogoče potrditi pripravljenosti;
- relevantna sprememba umakne aktivno potrditev in ohrani njeno zgodovino;
- uporabnik nima dostopa do zadev in dokumentov drugega podjetja ali nedostopnega projekta;
- obstoječi prikaz projekta in dokumentov ostane uporaben.

Avtomatizirani testi naj pokrijejo izračun stanj, povezovanje dopolnitev, razveljavitev potrditev, avtorizacijo in integriteto dokumentnih povezav. Ročno preverimo obrazce, časovnico, razumljivost oznak in prikaz na ozkem zaslonu. Za prenos posebej preverimo neznane podatke in ponovljivost brez podvojitev.

## 10. Meja prve različice

Prva različica zajema evidenco postopka, dokumentne povezave, zgodovino, izračun stanj in interne potrditve. Samodejno pošiljanje, branje elektronske pošte, zakonski roki, pravna presoja ter splošen sistem za poljubne upravne postopke so zunaj tega obsega.

Prednostni vrstni red je: **zanesljiva evidenca → prenos obstoječih podatkov → organizacija dela → avtomatizacija**.
