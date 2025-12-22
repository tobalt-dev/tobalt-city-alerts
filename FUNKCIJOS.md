# Tobalt City Alerts - Pranešimų Sistema

**Autorius:** Tobalt — https://tobalt.lt
**Versija:** 1.3.3

---

## Apie įskiepį

„Tobalt City Alerts" yra WordPress įskiepis, skirtas savivaldybėms ir organizacijoms, norinčioms informuoti gyventojus apie vykstančius darbus, sutrikimus ar įvykius. Sistema leidžia darbuotojams pateikti pranešimus be WordPress paskyros, naudojant saugią „magic link" autentifikaciją.

---

## Pagrindinės funkcijos

### 1. Pranešimų valdymas

- **Pranešimų tipai:** Vandentiekio darbai, elektros darbai, kelio darbai, šilumos tinklų darbai, interneto/ryšio sutrikimai, kiti pranešimai
- **Būsenos:** Juodraštis, laukia peržiūros, publikuotas, archyvuotas
- **Automatinis archyvavimas:** Pranešimai automatiškai archyvuojami pasibaigus nurodytai datai
- **Rankinis archyvavimas:** Galimybė pažymėti pranešimą kaip išspręstą

### 2. Magic Link autentifikacija

Saugi prisijungimo sistema be slaptažodžių:

- Administratorius prideda patvirtintus el. pašto adresus
- Darbuotojas įveda savo el. paštą ir gauna unikalią nuorodą
- Nuoroda galioja nustatytą laiką (numatyta: 60 min.)
- Apsauga nuo piktnaudžiavimo (užklausų limitas per valandą)

### 3. Pranešimų pateikimo forma

- **Responsiyvus dizainas:** Pritaikytas mobiliesiems įrenginiams
- **WCAG 2.1 AA:** Atitinka prieinamumo standartus
- **Kortelių sistema:** „Naujas pranešimas" ir „Mano pranešimai"
- **Google reCAPTCHA v3:** Apsauga nuo automatinių pateikimų

### 4. „Mano pranešimai" funkcija

Pateikėjai gali:

- Matyti visus savo pateiktus pranešimus
- Redaguoti planuojamą pabaigos datą
- Pažymėti pranešimą kaip išspręstą
- Stebėti pranešimų būsenas

### 5. Veiklos žurnalas (Activity Log)

Administratoriai mato:

- **Statistiką:** Viso sukurta, išspręsta, pasibaigė, vidutinis sprendimo laikas
- **Detalų žurnalą:** Kas, ką, kada atliko
- **Top pateikėjus:** Aktyviausių darbuotojų sąrašas
- **Filtravimą:** Pagal veiksmą, el. paštą, datų intervalą

### 6. Viešas pranešimų rodymas

- **Shortcode:** `[tobalt_city_alerts]`
- **Filtravimas:** Pagal tipą, būseną
- **Interaktyvus žemėlapis:** OpenStreetMap integracija
- **Spalvų kodavimas:** Skirtingos spalvos pagal pranešimo tipą

### 7. El. pašto pranešimai

- Magic link siuntimas pateikėjams
- Pranešimas administratoriui apie naujus pateikimus
- Pritaikomi siuntėjo vardas ir adresas

---

## Naudojimo scenarijai

### Scenarijus 1: Vandentiekio avarija

1. Vandentiekio įmonės darbuotojas atidaro pateikimo formą
2. Įveda savo patvirtintą el. paštą
3. Gauna magic link į el. paštą
4. Paspaudęs nuorodą, užpildo formą:
   - **Tipas:** Vandentiekio darbai
   - **Pavadinimas:** Vandentiekio avarija Vytauto g.
   - **Aprašymas:** Dėl vamzdžio lūžimo nutrauktas vandens tiekimas
   - **Adresas:** Vytauto g. 15, Vilnius
   - **Planuojama pabaiga:** 2024-01-15 18:00
5. Administratorius patvirtina pranešimą
6. Gyventojai mato pranešimą viešame puslapyje
7. Sutaisius avariją, darbuotojas pažymi „Išspręsta"

### Scenarijus 2: Planuojami elektros darbai

1. Elektros tinklų darbuotojas gauna magic link
2. Sukuria pranešimą iš anksto:
   - **Tipas:** Elektros darbai
   - **Pavadinimas:** Planuojami elektros tinklų darbai
   - **Aprašymas:** Bus atnaujinami elektros tinklai. Numatomas elektros tiekimo nutraukimas 4 val.
   - **Planuojama pabaiga:** 2024-01-20 16:00
3. Pranešimas publikuojamas ir gyventojai informuojami iš anksto
4. Pasibaigus darbams, pranešimas automatiškai archyvuojamas

### Scenarijus 3: Kelio remonto darbai

1. Kelių priežiūros darbuotojas pateikia pranešimą:
   - **Tipas:** Kelio darbai
   - **Pavadinimas:** Kelio dangos remontas
   - **Aprašymas:** Vykdomi asfalto dangos remonto darbai. Prašome rinktis alternatyvius maršrutus.
   - **Vieta:** Pažymima žemėlapyje
   - **Planuojama pabaiga:** 2024-02-01
2. Darbams užsitęsus, darbuotojas atidaro „Mano pranešimai"
3. Pakeičia pabaigos datą į naują
4. Baigus darbus, pažymi kaip išspręstą

### Scenarijus 4: Administravimas

1. Administratorius atidaro „Pranešimai gyventojams"
2. Peržiūri laukiančius patvirtinimo pranešimus
3. Patikrina informacijos tikslumą
4. Patvirtina arba atmeta pranešimą
5. „Veiklos žurnalas" skiltyje mato:
   - Kas pateikė pranešimą
   - Kada buvo publikuotas
   - Kiek laiko užtruko išspręsti
   - Aktyviausius pateikėjus

---

## Techninė informacija

### Shortcode'ai

```
[tobalt_city_alerts]                    # Išskleidžiamas pranešimų skydelis
[tobalt_city_alerts inline="true"]      # Įterptas pranešimų rodinys (kalendorius, navigacija, įvykiai)
[tobalt_subscribe]                      # El. pašto prenumeratos forma
[tobalt_request_link]                   # Magic link užklausos forma
[tobalt_submission_form]                # Pranešimų pateikimo forma (alias [tobalt_request_link])
```

### Pranešimų tipai (slugs)

- `water` - Vandentiekio darbai
- `electricity` - Elektros darbai
- `road` - Kelio darbai
- `heating` - Šilumos tinklų darbai
- `internet` - Interneto/ryšio sutrikimai
- `other` - Kiti pranešimai

### REST API endpoints

- `GET /wp-json/tobalt-city-alerts/v1/alerts` - Gauti pranešimus
- `POST /wp-json/tobalt-city-alerts/v1/request-link` - Užklausti magic link
- `POST /wp-json/tobalt-city-alerts/v1/submit` - Pateikti pranešimą
- `GET /wp-json/tobalt-city-alerts/v1/my-alerts` - Mano pranešimai
- `POST /wp-json/tobalt-city-alerts/v1/mark-solved/{id}` - Pažymėti išspręstu

### Duomenų bazės lentelės

- `wp_tobalt_magic_tokens` - Magic link tokenai
- `wp_tobalt_approved_emails` - Patvirtinti el. paštai
- `wp_tobalt_alert_activity_log` - Veiklos žurnalas

---

## Nustatymai

### Pagrindiniai nustatymai

| Nustatymas | Aprašymas | Numatyta reikšmė |
|------------|-----------|------------------|
| Token galiojimas | Magic link galiojimo laikas minutėmis | 60 |
| Užklausų limitas | Max užklausų per valandą vienam el. paštui | 3 |
| El. pašto siuntėjas | Siuntėjo vardas ir adresas | Svetainės nustatymai |

### reCAPTCHA nustatymai

1. Sukurkite reCAPTCHA v3 raktus: https://www.google.com/recaptcha/admin
2. Įveskite Site Key ir Secret Key nustatymuose
3. Nustatykite minimalų balą (rekomenduojama: 0.5)

---

## Diegimas

1. Įkelkite `tobalt-city-alerts` aplanką į `/wp-content/plugins/`
2. Aktyvuokite įskiepį WordPress administratoriaus skiltyje
3. Eikite į „Pranešimai gyventojams → Nustatymai"
4. Pridėkite patvirtintus el. pašto adresus
5. Sukurkite puslapį su shortcode `[tobalt_city_alerts]` arba `[tobalt_city_alerts inline="true"]`
6. Sukurkite pateikimo puslapį su shortcode `[tobalt_submission_form]` (darbuotojams gauti magic link)

---

## Papildomos funkcijos (neįdiegtos)

Šios funkcijos gali būti įdiegtos ateityje pagal poreikį:

### 1. Prenumeratos sistema

Gyventojai galėtų prenumeruoti pranešimus ir gauti automatinius pranešimus apie naujus įvykius:

- **El. pašto prenumerata** - gyventojas įveda el. paštą ir gauna pranešimus apie naujus įspėjimus
- **Filtravimas pagal tipą** - galimybė pasirinkti tik dominančius pranešimų tipus (pvz., tik vandentiekio ar elektros)
- **Filtravimas pagal vietą** - pranešimai tik apie įvykius konkrečioje teritorijoje
- **Prenumeratos valdymas** - galimybė atsisakyti prenumeratos vienu paspaudimu

### 2. Push pranešimai

Naršyklės push pranešimai realiu laiku:

- Momentiniai pranešimai apie naujus įspėjimus
- Veikia net kai naršyklė uždaryta
- Nereikia el. pašto adreso

### 3. RSS kanalas

Automatiškai generuojamas RSS kanalas:

- Integracija su RSS skaitytuvėmis
- Automatinis atnaujinimas
- Filtravimas pagal pranešimo tipą

### 4. SMS pranešimai

Pranešimai trumposiomis žinutėmis:

- Integracija su SMS tiekėjais (Twilio, MessageBird ir kt.)
- Kritinių pranešimų siuntimas SMS
- Telefono numerio patvirtinimas

---

## Palaikymas

Kilus klausimams ar problemoms, kreipkitės:

- **Svetainė:** https://tobalt.lt
- **El. paštas:** info@tobalt.lt
