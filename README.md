# NLSB Slider

Een flexibele en toegankelijke slider-plugin voor WordPress, met ondersteuning voor meerdere layouts en een globale infokaart.

## Functies

- **Custom post type**:  
  - `nlsb_slider` (container voor slides)  
  - `nlsb_slide` (individuele slides, gekoppeld aan een slider)

- **Layouts**:
  - **Type A** – Tekstpaneel links, afbeelding als achtergrond  
    - Op mobiel inklapbaar met een toggle-knop  
  - **Type B** – Caption-balk onderin, afbeelding als achtergrond  

- **Globale info**:
  - Gebaseerd op de eerste slide van de slider  
  - Toonbaar via een centrale **+** knop boven de slider  
  - Opent als modaalvenster (overlay) met titel, tekst en optionele knop  
  - Content wordt consistent op **alle slides** getoond

- **Navigatie**:
  - Vorige/volgende knoppen  
  - Dots/paginatie  
  - Keyboard support (pijltjestoetsen)  
  - Oneindig scrollen met clones voor naadloze loop

- **Toegankelijkheid**:
  - ARIA-roles en labels  
  - Toggle- en close-buttons met `aria-expanded`  
  - Esc-toets sluit het modaal

## Shortcode

Gebruik de shortcode om een slider in te voegen:

```text
[nlsb_slider id="123"]

[nlsb_slider slug="home-slider"]
