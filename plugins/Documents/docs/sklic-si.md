# Sklic na računu: referenci SI in RF

Plačilni sklic je sestavljen iz treh polj računa: `pmt_type` (vrsta, `SI` ali `RF`), `pmt_module` (dvomestni model pri
`SI`, kontrolni števki pri `RF`) in `pmt_ref` (vsebina). Elektronski zapis, ki gre v UPN QR, eSlog in SEPA, je njihov
stik: `SI` + `12` + `0000002026023` → `SI120000002026023`, oziroma `RF` + `71` + `2348231` → `RF712348231`.

- [Pravila](#pravila)
- [Referenca SI](#referenca-si)
- [Referenca RF](#referenca-rf)
- [Generiranje sklica](#generiranje-sklica)
- [Validacija](#validacija)
- [Kje se sklic prikaže](#kje-se-sklic-prikaže)
- [Kje se sklic uporabi](#kje-se-sklic-uporabi)

## Pravila

Podlaga so *Pravila za oblikovanje in uporabo standardiziranih referenc pri opravljanju plačilnih storitev*, Združenje
bank Slovenije, verzija 1.4 (uporabljajo se od 1. 12. 2023), objavljena na [zbs-giz.si](https://www.zbs-giz.si/).
Referenca SI je domači standard, referenca RF pa je po ISO 11649 priporočena s pravili SEPA in v uporabi po vsem
območju SEPA, zato je primernejša za čezmejne račune.

## Referenca SI

| Mesto | Vsebina |
|---|---|
| 1–2 | konstanta `SI` |
| 3–4 | številka modela |
| 5–26 | vsebina modela: največ 22 znakov, od tega do 20 številk in največ dva vezaja |

Vsebina je zapisana z enim, dvema ali tremi podatki (`P1-P2-P3`), ločenimi z vezajem. Posamezen podatek je dolg največ
12 številk, vsi trije skupaj največ 20 številk, ne glede na število vezajev. Izjema je model 12: en sam podatek dolžine
natanko 13 znakov s kontrolno številko in brez vezaja. Podatka `P2` in `P3` se zapišeta brez vodilnih ničel. V
elektronskem zapisu ni presledkov; vizualno se za modelom zapiše presledek (`SI05 19-1235-84503`).

### Modeli

Vsi modeli iz pravil so v [`SiReference::MODELS`](../src/Lib/SiReference.php) z zahtevanim in dovoljenim številom
podatkov ter mesti kontrolnih številk. Za izdane račune se uporablja **model 12**: pravila ga v prilogi 1 navajajo kot
primer obrazca UPN QR, ima eno samo polje fiksne dolžine s kontrolno številko in je zato najbolj robusten za strojno
prepoznavo plačil. Model 00 je brez kontrolne številke in ne ujame tipkarske napake plačnika, model 99 je brez vsebine
in se uporabi, kadar uporabnega sklica ni.

Modeli 22 in 32 so rezervirani za FURS (SEPA direktna obremenitev), model 58 za enotni zakladniški račun; v računih jih
ne uporabljaj.

### Kontrolna številka (modul 11)

Kontrolna številka je zadnja številka podatka in je njegov sestavni del. Izračuna se po modulu 11 (priloga 3 pravil):
številke podatka se pomnožijo s ponderji, ki se začnejo z 2 na desni in rastejo proti levi, zmnožki se seštejejo,
seštevek se deli z 11, ostanek pa se odšteje od 11. Če je rezultat 10 ali 11, je kontrolna številka 0.

Kontrolne številke se preverjajo samo tam, kjer pravila opisujejo posamezen podatek z lastno kontrolno številko. Pri
modelih, kjer ena kontrolna številka ščiti skupino podatkov (01, 06, 08, 09, 10), in pri modelih, katerih strukturo
pravila opisujejo dvoumno (21, 22, 23, 31, 32), se preverijo samo splošne kontrole, da veljaven sklic ni nikoli
zavrnjen.

## Referenca RF

| Mesto | Vsebina |
|---|---|
| 1–2 | konstanta `RF` |
| 3–4 | kontrolni števki po MOD 97-10 (ISO/IEC 7064) |
| 5–25 | vsebina: največ 21 številk in črk |

Vezaji, presledki in drugi posebni znaki niso dovoljeni; dovoljene so številke ter male in velike črke od A do Z. V
elektronskem zapisu se referenca piše brez presledkov, vizualno pa v sklopih po štiri znake (`RF71 2348 231`).
Pretvorba črk v številke za izračun je `A = 10` … `Z = 35` (`RF` postane `2715`).

Kontrolni števki se izračunata tako, da se vsebini na desni doda `RF00`, črke se pretvorijo v številke, izračuna se
ostanek pri deljenju z 97, ta pa se odšteje od 98 in po potrebi dopolni z vodilno ničlo (priloga 4 pravil). Pravilnost
se preveri tako, da se prvi štirje znaki prestavijo na konec; ostanek pri deljenju z 97 mora biti 1 (priloga 5).
Primeri iz pravil: vsebina `2348231` da `RF712348231`, vsebina `SBO2010` pa `RF45SBO2010`. Implementacija je v
[`RfReference`](../src/Lib/RfReference.php).

## Generiranje sklica

`Invoice::generatePaymentReference()` ob izdaji novega dokumenta (`getNextCounterNo()`) napolni `pmt_type`,
`pmt_module` in `pmt_ref`, kadar:

- je števec smeri *issued* in
- je `pmt_ref` prazen (uvožen ali ročno vnesen sklic se nikoli ne prepiše).

Vrsto in model določa `pmt_mod` števca: `12` (privzeto) ali `00` za referenco SI, `RF` za referenco RF. Vsebina se
izpelje iz številke dokumenta, zato je za isti dokument vedno enaka:

- če je na števcu nastavljena predloga `pmt_ref`, se uporabi ta (nadomestni nizi `[[no]]`, `[[year]]`, `[[month]]`),
- sicer številka dokumenta, pred katero se postavi leto izdaje, če se z njim še ne začne.

Znaki, ki jih referenca ne dovoljuje, se izpustijo: pri SI ostanejo samo številke, pri RF številke in črke (velike).
Pri modelu 12 se dobljene številke skrajšajo na zadnjih 12, dopolnijo z vodilnimi ničlami in dobijo kontrolno številko;
pri RF se vsebina skrajša na zadnjih 21 znakov in dobi kontrolni števki.

| Števec `pmt_mod` | Dokument `ISU-02`, izdan 2026 | Sklic |
|---|---|---|
| `12` (privzeto) | `202602` | `SI12 0000002026023` |
| `00` | `202602` | `SI00 202602` |
| `RF` | `2026ISU02` | `RF02 2026ISU02` |

## Validacija

`InvoicesTable::validationDefault()` zavrne neveljaven vnos:

| Polje | Pravilo |
|---|---|
| `pmt_type` | prazno, `SI` ali `RF` (koda namena, npr. `OTHR`, sodi v `pmt_sepa_type`) |
| `pmt_module` | pri `SI` eden od modelov iz pravil, pri `RF` dve števki |
| `pmt_ref` | pri `SI` znaki, dolžine, število podatkov, vodilne ničle in kontrolne številke modela; pri `RF` dovoljeni znaki, dolžina in kontrolni števki |

Pri prejetih računih (števec smeri *received*) je sklic izdajateljev, zato se kontrolne številke ne preverjajo in
napačna kontrolna številka ne prepreči vpisa; splošne kontrole veljajo tudi tam. Sklic brez vrste (prazen `pmt_type`)
se ne preverja, ker uvoz eSlog v `pmt_ref` zapiše tudi tuje reference, ki teh pravil ne upoštevajo.

Na števcu (`DocumentsCountersTable`) je `pmt_mod` omejen na vrednosti, ki jih znamo generirati (`00`, `12`, `RF`),
`pmt_ref` pa na številke, vezaje in nadomestne nize.

## Kje se sklic prikaže

`Invoice::paymentReference()` vrne elektronski zapis (stik treh polj), `paymentReference(true)` pa zapis za prikaz:
`SI12 0000002026023`, referenco RF pa v sklopih po štiri znake (`RF71 2348 231`).

| Mesto | Prikaz |
|---|---|
| Obrazec računa (`templates/Invoices/edit.php`) | pri izdanih računih brez polj, samo izpis sklica |
| Pregled dokumenta (`templates/Invoices/view.php`) | vrstica *Sklic* med podatki dokumenta |
| Izpis računa (`webroot/doc_eslog.xslt`) | vrstica *Sklic*, oblikovana kot `SI00 - 01` |

Pri izdanih računih sklic ustvari števec, zato obrazec polj `pmt_type`, `pmt_module` in `pmt_ref` ne prikaže: pri novem
dokumentu pove, da bo sklic ustvarjen ob shranjevanju, pri shranjenem pa izpiše sklic, ki ga dokument ima. Starejši
izdani dokument brez sklica polja obdrži, da ga je mogoče vnesti. Pri prejetih in drugih dokumentih so polja vedno na
voljo.

Izpis računa nastane iz eSlog XML (`templates/Invoices/xml/eslog.php`), ki ga `InvoicesExport::toHtml()` pretvori z
XSLT. Privzeta predloga `webroot/doc_eslog.xslt` sklic izpiše (predloga `format_sklic` loči prve štiri znake od
vsebine). Podjetje z lastno predlogo (`tpl_body`) mora sklic v svoj XSLT dodati samo, saj se privzeta predloga takrat
ne uporabi.

### UPN QR na izpisu

UPN QR se ustvari v `DocumentsUpnQr::generateUpnQr()` in se v eSlog XML zapiše kot `PoljubnoBesediloRazsirjeno` z
`ImeBesedila = UPNQR`; vrednost je `data:image/gif;base64,…`, razrezan na elemente `Tekst` po 70 znakov. Privzeta
predloga `doc_eslog.xslt` te slike ne izpiše, zato je na natisnjenem računu sklic, ne pa tudi QR koda. Za prikaz bi
bilo treba v `doc_eslog.xslt` dodati `<img>`, katerega `src` sestavi vrednost elementov `Tekst` brez presledkov
(v XSLT 1.0 na primer `translate(VrednostBesedila, ' &#9;&#10;&#13;', '')`, ker je XML lepo oblikovan in so med
elementi prelomi vrstic), ter določiti velikost, pri kateri je koda še berljiva za bančne aplikacije. Ker to spremeni
postavitev vsakega izpisanega računa, tu ni narejeno.

## Kje se sklic uporabi

| Mesto | Polje |
|---|---|
| UPN QR | `referenca_prejemnika` (`DocumentsUpnQr`), omejeno na 26 znakov |
| eSlog 1.6 | `SklicZaPlacilo/StevilkaSklica` |
| eSlog 2.0 | `G_SG1/S_RFF/C_C506/D_1154` ob `D_1153 = PQ` |
| SEPA | `RmtInf/Strd/CdtrRefInf/Ref` |

Povsod gre isti elektronski zapis brez presledkov.
