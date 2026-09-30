# REST API: izdaja računa

Zunanji sistem pošlje račun v formatu **eSlog 2.0**. Arhint ga vpiše v izbrani števec izdanih računov, po potrebi opravi
davčno potrjevanje (FURS) in vrne PDF računa.

- [Klic](#klic)
- [Avtentikacija](#avtentikacija)
- [Glave](#glave)
- [Telo zahtevka (eSlog 2.0)](#telo-zahtevka-eslog-20)
- [Odgovor ob uspehu](#odgovor-ob-uspehu)
- [Napake](#napake)
- [Idempotentnost](#idempotentnost)
- [Davčno potrjevanje](#davčno-potrjevanje)
- [Primeri](#primeri)
- [Nastavitve in omejitve](#nastavitve-in-omejitve)

## Klic

```
POST /documents/api/invoices?counter=<id števca>
```

| Parameter | Obvezen | Opis |
|---|---|---|
| `counter` | da | UUID števca. Števec mora biti aktiven, vrste *Invoices* in smeri *Issued* ter pripadati podjetju uporabnika. ID najdeš na strani Lookups → Counters (povezava za urejanje števca). |

Podprta je samo metoda `POST`; `GET` in ostale vrnejo `404`.

## Avtentikacija

HTTP **Basic**: uporabniško ime in geslo obstoječega uporabnika aplikacije.

```
Authorization: Basic base64(uporabnik:geslo)
```

Uporabnik mora imeti dostop do modula Documents in vlogo *editor* ali višjo. Račun se izda v imenu tega uporabnika:

- **izdajatelj** računa so vedno podatki podjetja uporabnika (element `SE` v XML-ju se prezre),
- za davčno potrjevanje se uporabi **certifikat tega uporabnika** (stran Poslovni prostori → Certificate).

Priporočamo posebnega uporabnika za povezavo. Povezava mora biti prek HTTPS.

## Glave

| Glava | Obvezna | Opis |
|---|---|---|
| `Authorization` | da | `Basic …` |
| `Content-Type` | ne | `application/xml` (telo se vedno bere kot XML) |
| `Idempotency-Key` | ne | Ključ za varno ponavljanje klica. 1–255 tiskljivih ASCII znakov. Glej [Idempotentnost](#idempotentnost). |

## Telo zahtevka (eSlog 2.0)

Telo je surov XML dokument eSlog 2.0 (`<Invoice xmlns="urn:eslog:2.00"><M_INVOIC>…`). Upoštevajo se samo spodnji
elementi.

| Podatek na računu | Element eSlog 2.0 | Opomba |
|---|---|---|
| Številka računa | `S_BGM/C_C106/D_1004` | Če ima števec masko, se številka vedno ustvari iz števca. |
| Naslov | `S_FTX` z `D_4451 = AAI`, `C_C108/D_4440` | Privzeto naslov števca. |
| Datum izdaje | `S_DTM` `D_2005 = 137` | Privzeto današnji datum. |
| Datum storitve | `S_DTM` `D_2005 = 35` | Privzeto datum izdaje. |
| Datum zapadlosti | `G_SG8/S_DTM` `D_2005 = 13` | Privzeto datum izdaje + rok plačila števca (8 dni). |
| Sklic | `G_SG1/S_RFF/C_C506` `D_1153 = PQ`, `D_1154` | Npr. `SI00123456` → model `SI00`, sklic `123456`. |
| Kupec | `G_SG2/S_NAD` `D_3035 = BY` | `C_C080/D_3036` naziv (obvezen), `C_C059/D_3042` ulica, `D_3164` kraj, `D_3251` pošta, `D_3207` država |
| Davčna št. kupca | `G_SG2/G_SG3/S_RFF` `D_1153 = VA`, `D_1154` | Pošlje se tudi FURS. |
| E-pošta kupca | `G_SG2/G_SG5/S_COM/C_C076/D_3148` | |
| Postavka: opis | `G_SG26/S_IMD/C_C273/D_7008` | |
| Postavka: količina, enota | `G_SG26/S_QTY/C_C186` `D_6060`, `D_6411` | |
| Postavka: cena | `G_SG26/G_SG29/S_PRI/C_C509` `D_5125 = AAA`, `D_5118` | Cena brez DDV. |
| Postavka: stopnja DDV | `G_SG26/G_SG34/S_TAX/C_C243/D_5278` | Stopnja mora obstajati v šifrantu DDV podjetja. |

Zneske (osnova, DDV, skupaj) izračuna Arhint iz postavk. Vsota iz `G_SG50` se ne uporabi.

Primer celotnega dokumenta: `plugins/Documents/tests/TestCase/Controller/data/testInvoice_eslog20.xml`.

## Odgovor ob uspehu

`201 Created`, `Content-Type: application/pdf`, telo je PDF računa (`Content-Disposition: attachment`).

| Glava odgovora | Opis |
|---|---|
| `X-Invoice-Id` | UUID ustvarjenega računa v Arhintu |
| `X-Invoice-No` | številka računa (URL kodirana) |
| `X-Tax-Zoi` | ZOI (samo pri davčno potrjenih računih) |
| `X-Tax-Eor` | EOR (samo pri davčno potrjenih računih) |
| `Idempotent-Replayed` | `true` pri ponovljenem klicu z istim `Idempotency-Key` (status je tedaj `200`) |

## Napake

Vse napake so JSON (razen `401`, ki nima telesa):

```json
{ "error": "tax_confirmation_failed", "message": "s005: …", "tax_error_code": "CONFIRMATION_ERROR_XML" }
```

| Status | `error` | Pomen | Ponovi? |
|---|---|---|---|
| 401 | – | Manjkajoča ali napačna prijava (`WWW-Authenticate: Basic`) | po popravku |
| 403 | `forbidden` | Uporabnik nima dostopa do Documents ali vloge editor | ne |
| 404 | `counter_not_found` | Števec ne obstaja ali ni od podjetja uporabnika | ne |
| 400 | `invalid_idempotency_key` | `Idempotency-Key` ni 1–255 tiskljivih ASCII znakov | po popravku |
| 400 | `empty_body` | Prazno telo | po popravku |
| 400 | `invalid_xml` | Telo ni veljaven eSlog 2.0 (`message` vsebuje razlog) | po popravku |
| 422 | `invalid_counter` | Števec ni aktiven števec izdanih računov | ne |
| 422 | `invalid_invoice` | Brez postavk, brez kupca ali neznana stopnja DDV | po popravku |
| 422 | `invoice_not_saved` | Račun ni prestal preverjanja (`errors` vsebuje polja) | po popravku |
| 422 | `idempotency_key_reused` | Isti ključ je bil uporabljen z drugačnim zahtevkom | z novim ključem |
| 422 | `tax_confirmation_failed` | Napaka nastavitev: manjka certifikat, poslovni prostor ali davčna št. izdajatelja (`tax_error_code`) | po popravku nastavitev |
| 409 | `request_in_progress` | Zahtevek z istim ključem se še obdeluje (`Retry-After: 5`) | da, čez nekaj sekund |
| 502 | `tax_confirmation_failed` | FURS je zavrnil račun ali ni dosegljiv (`message`, `tax_error_code`) | da |
| 500 | `server_error` | Notranja napaka, račun ni nastal | da |
| 500 | `pdf_failed` | Račun je **nastal** in je potrjen, PDF pa se ni izrisal (`invoice_id`, `tax_zoi`, `tax_eor`) | da, **z istim ključem** |

Vrednosti `tax_error_code`: `CONFIRMATION_ERROR_NO_CERTIFICATE`, `CONFIRMATION_ERROR_NO_PREMISE`,
`CONFIRMATION_ERROR_TAXNO`, `CONFIRMATION_ERROR_SIGN`, `CONFIRMATION_ERROR_REQUEST` (transport, npr. timeout) in
`CONFIRMATION_ERROR_XML` (FURS je vrnil napako).

Kadar ustvarjanje ali davčna potrditev ne uspe, se **vse razveljavi**: račun ni ustvarjen in števec se ne poveča.

## Idempotentnost

Če odjemalec ne prejme odgovora (izgubljena povezava, timeout), ne ve, ali je račun nastal. Zato pošlje ob vsakem
računu unikaten `Idempotency-Key` (npr. UUID ali ID naročila) in ob ponovitvi pošlje **isti ključ z istim telesom**.

- Ključ velja **na uporabnika**: isti ključ različnih uporabnikov je neodvisen.
- Prvi klic: račun se ustvari, odgovor `201`.
- Ponovitev po uspehu: račun se **ne ustvari znova**, vrne se PDF istega računa, status `200`,
  `Idempotent-Replayed: true`, enake glave `X-Invoice-*` in `X-Tax-*`.
- Ponovitev po napaki (`4xx`, `5xx` razen `pdf_failed`): ključ se sprosti, ponovni klic obdela zahtevek znova.
- Isti ključ z drugačnim telesom ali števcem: `422 idempotency_key_reused`.
- Hkratni klic z istim ključem: `409 request_in_progress`. Ključ prekinjenega zahtevka se po 5 minutah sprosti.
- Brez glave se vsak uspešen klic šteje za nov račun.

Ključ hrani zapise trajno (tabela `documents_api_requests`); enako ključu ne uporabljaj ponovno za drug račun.

## Davčno potrjevanje

Če ima števec vklopljeno *Tax Confirmation*, Arhint pred vrnitvijo računa izračuna ZOI, ga podpiše s certifikatom
uporabnika, pošlje FURS in shrani EOR. Pogoji:

- na števcu sta izbrana **poslovni prostor** (prijavljen pri FURS) in **oznaka naprave**,
- uporabnik ima naložen **certifikat** (Poslovni prostori → Certificate),
- davčna številka izdajatelja (podjetje uporabnika) je enaka številki v certifikatu.

PDF davčno potrjenega računa vsebuje QR kodo, ZOI in EOR. FURS klic ima omejitev 30 sekund
(`Documents.furs.timeout`).

## Primeri

### curl

```bash
curl -i -X POST \
  -u api-uporabnik:geslo \
  -H "Content-Type: application/xml" \
  -H "Idempotency-Key: narocilo-1001" \
  --data-binary @racun.xml \
  -o racun.pdf \
  "https://arhint.example.com/documents/api/invoices?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89"
```

Uspeh:

```
HTTP/1.1 201 Created
Content-Type: application/pdf
X-Invoice-Id: 6c9c8f0e-5d0c-4b52-9d31-1c1d7d6f7a11
X-Invoice-No: 2026-0012
X-Tax-Zoi: 065584a8af4bba9d0423f0a9fc16f3e0
X-Tax-Eor: 99997245-7a89-4053-8221-c50d785bfbbf
```

Zavrnitev FURS:

```
HTTP/1.1 502 Bad Gateway
Content-Type: application/json

{"error":"tax_confirmation_failed","message":"s005: Davčna številka ni enaka davčni številki v potrdilu",
 "tax_error_code":"CONFIRMATION_ERROR_XML"}
```

### PHP

```php
$ch = curl_init('https://arhint.example.com/documents/api/invoices?counter=' . $counterId);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $eslogXml,
    CURLOPT_HTTPHEADER => ['Content-Type: application/xml', 'Idempotency-Key: ' . $orderId],
    CURLOPT_USERPWD => 'api-uporabnik:geslo',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 60,
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
```

Priporočen potek pri odjemalcu: ob `201`/`200` shrani PDF; ob `409`, `502`, `5xx` in prekinjeni povezavi ponovi
**isti klic z istim `Idempotency-Key`** (z zamikom); ostale `4xx` napake so trajne, zahtevek je treba popraviti.

## Nastavitve in omejitve

- `Documents.furs.production` (`config/app_local.php`): `false` = FURS testno okolje, `true` = produkcija.
- `Documents.furs.timeout`: skupni čas FURS klica v sekundah (privzeto 30). Nastavi `max_execution_time` in
  timeout spletnega strežnika nad tem časom (klic vključuje še izris PDF).
- HTTP Basic zahteva, da strežnik PHP-ju posreduje `PHP_AUTH_USER` in `PHP_AUTH_PW` (Apache `mod_php` to naredi;
  pri FastCGI je treba glavo `Authorization` posredovati v nastavitvi strežnika).
- Dokument mora vsebovati stopnje DDV, ki obstajajo v šifrantu DDV podjetja. Neznana stopnja se zavrne, ker bi jo
  FURS sicer prejel kot oproščeno DDV.
- Vsak klic ustvari en račun. Paketnega uvoza ni.
