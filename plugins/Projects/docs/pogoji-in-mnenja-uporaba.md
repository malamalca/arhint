# Pogoji in mnenja — uporaba

Na projektu odprite zavihek **Pogoji in mnenja**. Soglasodajalci so obstoječa podjetja iz Kontaktov; izbirate jih z autocomplete.

## Dodajanje soglasodajalca

V zgornjem glavnem meniju izberite **Vpis → Dodaj soglasodajalca**. Izberite podjetje, vnesite naziv zadeve oziroma področja ter po želji odgovorno osebo, številko zadeve in opombe.

Nastavitev potrebnosti projektnih pogojev in mnenj ni. Vsak dodani soglasodajalec šteje v pregled pridobivanja mnenj. Odgovorna oseba ostaja shranjena, v pregledni tabeli pa ni prikazana.

**Ni potrebno.** Če soglasodajalec za projekt ni potreben, v polje *Ni potrebno: razlog* vpišite razlog. Zadeva se tedaj izključi iz pregleda (ne šteje med manjkajoča mnenja in ne blokira pripravljenosti), v tabeli pa ostane s stanjem *Ni potrebno*. Če razlog izbrišete, zadeva spet šteje.

**Brisanje.** Zadevo brez evidentiranih dogodkov izbrišete z gumbom *Izbriši zadevo* na njeni strani. Zadeve z zgodovino ni mogoče izbrisati; če ni potrebna, ji vpišite razlog *Ni potrebno*.

## Dogodki

Na zadevi v zgornjem meniju izberite **Evidentiraj dogodek postopka**. Dogodek ni vezan na fazo ali različico dokumentacije.

Obstoječi veljavni dogodek uredite z gumbom **Uredi** v zgodovini. Popravite lahko datum, rok, povzetek, povezani dogodek in dokumente. Spremembe se zapišejo v projektni dnevnik; prvotni avtor in vrstni red ostaneta ohranjena. Sprememba vrste je dovoljena, če ne razveljavi pravil povezanih dogodkov.

Vnesite vrsto dogodka, datum oddaje oziroma prejema, po potrebi povezani dogodek, rok in povzetek. V enem polju **Povezani dokumenti** lahko izberete več dokumentov projekta.

- Vloga oddana lahko ostane brez dokumenta.
- Pogoji prejeti, mnenje prejeto in zahteva za dopolnitev zahtevajo dokument odgovora.
- Dopolnitev oddana se poveže z zahtevo za dopolnitev.
- Razveljavitev se poveže z dogodkom, ki ga razveljavlja (tudi z drugo razveljavitvijo). Razveljavitev razveljavitve povrne prvotni dogodek in odvisne dogodke.
- Drug odgovor, povezan z vlogo ali dopolnitvijo, konča čakanje na odgovor, vendar sam ničesar ne zaključi: mnenje mora še vedno prispeti.
- Prejeto mnenje zaključi postopek pri soglasodajalcu; potrditve mnenja ni. Postopek ponovno odprete z novo vlogo ali zahtevo (npr. ker je mnenje poteklo): zadeva je spet *Čakamo odgovor*, dokler ne prispe novo mnenje. Prejeto mnenje lahko tudi razveljavite.
- Rok ne sme biti pred datumom dogodka, dogodek pa ne pred povezanim dogodkom.

Izbirnik ponuja povezane dogodke glede na njihovo vrsto. Povezave niso omejene po nekdanjih fazah DPP/DGD. Obrazec samo evidentira dejanje; vloge ne pošilja.

## Pregledna tabela

Nad tabelo je po potrebi kratko opozorilo (odprti odgovori ali dopolnitve, nedostopen kontakt) in stanje pripravljenosti. Stanje posamezne zadeve je razvidno iz stolpca Status.

Tabela uporablja enako glavo in širino kot druge tabele v zavihkih. Prikaže soglasodajalca, stanje (Ni oddano, Čakamo odgovor, Potrebna dopolnitev, Zaključeno, Ni potrebno), projektne pogoje, mnenje in zahtevo za dopolnitev. Stolpca Naslednji korak in Odgovorna oseba sta skrita.

Vsaka odprta zahteva za dopolnitev (brez oddane dopolnitve) ima samostojen stolpec. Povezava **Rok: dd.mm.yyyy** odpre prvi povezani dokument. Pretečen rok je poudarjen z barvo. Rok druge vrste zadnjega dogodka se prikaže pod nazivom soglasodajalca. Ko novi zadnji dogodek nima roka, prejšnji rok ni več prikazan.

Za prejete pogoje oziroma mnenje se v ustreznem stolpcu pokaže datum izdaje prvega povezanega dokumenta na zelenem ozadju. Datum ostane viden tudi po potrditvi ustreznosti in po poznejših dogodkih, dokler odgovora ne razveljavite. Datum je povezava na dokument; kljukice ni. Če dokument nima datuma, povezava prikaže pomišljaj.

## Vrstni red soglasodajalcev

Vrstice v tabeli povlečete z ročajem (⋮) pred nazivom na želeno mesto; vrstni red se shrani sam za cel projekt. Novi soglasodajalec se doda na konec. Urejanje vrstnega reda ne razveljavi potrditve pripravljenosti. Povleci in spusti deluje z miško; na zaslonih na dotik ni na voljo.

## Pripravljenost za UE

Potrjevanja seznama soglasodajalcev in Opombe pregleda ni. Administrator lahko potrdi pripravljenost za UE, ko so mnenja vseh dodanih soglasodajalcev (razen tistih z razlogom *Ni potrebno*) prejeta in ni odprtih vlog oziroma dopolnitev. Šteje samo mnenje; projektni pogoji pripravljenosti ne pogojujejo. Prazen seznam ne pomeni pripravljenosti; če nobeden soglasodajalec ni potreben, dodajte zadevo z razlogom *Ni potrebno*.

Ročna potrditev oziroma njen preklic ostaneta evidentirana. Vsaka sprememba zadev, dogodkov in dokumentov samodejno razveljavi potrditev; potem jo je treba ponoviti. Različice dokumentacije se ne preverjajo.

Če se je postopek spremenil, medtem ko je bil obrazec odprt, shranjevanje ne uspe, obrazec ostane z vnesenimi podatki in prikaže se sporočilo; podatke preverite in shranite znova.

## Brisanje povezanih zapisov

Dokumenta, ki je povezan z dogodkom postopka, kontakta, ki je soglasodajalec na projektu, in projekta z zadevami soglasodajalcev ni mogoče izbrisati. Najprej je treba odstraniti povezavo oziroma prazne zadeve.

## Dnevnik

Spremembe zadeve in dogodka se v projektni dnevnik zapišejo samo kot seznam spremenjenih polj (staro in novo vrednost), ne kot celoten zapis.
