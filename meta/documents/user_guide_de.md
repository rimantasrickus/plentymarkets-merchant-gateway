![Logo](https://dev.heidelpay.com/devHeidelpay_400_180.jpg)

# heidelpay plentymarkets-merchant-gateway plugin
Dieses Plugin stellt eine Integration für die heidelpay Zahlungsarten für Ihren Plentymarkets Shop bereit.

Folgende Zahlungsarten werden aktuell unterstützt:
* Rechnungskauf
* Versicherter Rechnungskauf B2C
* Versicherter Rechnungskauf B2B
* Kreditkarte und Debitkarte
* SEPA (versicherte) Lastschrift
* PayPal
* SOFORT
* iDEAL
* FlexiPay Direct

## ANFORDERUNGEN
* Dieses Plugin ist entwickelt für Plentymarkets 7.

## Versionshinweise
Dieses Modul basiert auf dem heidelpay php-sdk (https://github.com/heidelpay/heidelpayPHP).

## Installation
+ Bitte lesen Sie [plentyKnowledge] (https://knowledge.plentymarkets.com), um zu erfahren, wie man Plugins installiert.
+ Nachdem Sie die unten beschriebenen Konfigurationsschritte durchgeführt haben, sollten Sie in der Lage sein, einige Tests im Staging-Modus durchzuführen.
+ Wenn alles in Ordnung ist, können Sie die Konfiguration in den Live-Modus ändern und das Plugin für den produktiven Bereich einsetzen, um es für Ihre Kunden zu aktivieren.

## Konfiguration
### Grundkonfiguration
+ Wählen Sie die Plugin-Registerkarte und dann "Plugin-Übersicht".
+ Wählen Sie das heidelpay-Plugin, um zur Konfigurationsübersicht zu wechseln.
+ Wählen Sie die Registerkarte `Standard-Containerlinks`. Wählen Sie alle Elemente in der Liste aus und speichern Sie die Konfiguration.
+ Für die Plugin-Konfiguration gehen Sie bitte zu `Einrichtung`->`Aufträge`->`Zahlung`->`Plugins`.

## Plugin-Einstellungen
####### Öffentlicher / Privater Schlüssel
Zum Erstellen von Zahlungsarten durch den Browser ist ein öffentlicher Schlüssel erforderlich.
Der private Schlüssel ist für die Authentifizierung auf dem heidelpay-Server zur Durchführung von Transaktionen erforderlich.

Der erste Buchstabe des jeweiligen Schlüssels bestimmt die Umgebung in der das Plugin arbeitet:
* `s` aktiviert den Testmodus, der gegen die Sandbox der API arbeitet (es findet kein Transfer von Geldern statt)
* `p` versetzt das Plugin in den Produktionsmodus. Hier finden tatsächliche Transaktionen statt.

> Wenn die `Speichern`-Taste gedrückt wird, registriert das Plugin heidelpay webhooks mit den dafür vorgesehenen Tasten.

####### Parameter der Zahlungsmethode
###### Aktiv
Wenn markiert, kann die Zahlungsmethode auf der Checkout-Seite ausgewählt werden.

###### Anzeigename
Der Name der Zahlungsmethode wird auf der Checkout-Seite unter angezeigt. \
Ein Standardname wird angezeigt, wenn er leer gelassen wird.

####### Min-/Max-Summe
Die Zahlungsmethode steht nur dann zur Verfügung, wenn der Warenkorb eine Summe zwischen diesen Werten aufweist.
Wenn Sie einen dieser Werte auf 0 setzen, wird die entsprechende Beschränkung deaktiviert.

####### URL zum Zahlungssymbol
Damit wird ein Symbol für die Zahlungsmethode definiert, das in der Kasse zusätzlich zum Anzeigenamen angezeigt wird.
Wenn leer gelassen, wird das Standardsymbol verwendet. \
Voraussetzungen für die url-Zeichenfolge:
* sie muss aus dem Internet erreichbar sein
* es muss mit 'http://' oder 'https://' beginnen
* es muss mit '.jpg', '.png' oder '.gif' enden

####### Kartenzahlungsmethode
Kartenzahlungen können auf zwei verschiedene Arten verwendet werden:
* Direktbelastung: das Bankkonto des Kunden wird direkt belastet.
* Autorisieren und belasten: Zuerst reservieren Sie Geld auf dem Konto des Kunden und später belasten Sie das Geld.

Wenn der Kunde eine Karte hat, die 3D-Sicherheit verwendet, wird er während des Bestellvorgangs auf eine Seite weitergeleitet, auf der er seine Zahlung autorisieren kann.
Wenn der Kunde aus irgendeinem Grund nicht in der Lage sein wird, eine Autorisierung vorzunehmen, wird er zurück zur Kasse umgeleitet.
Andererseits, wenn alles in Ordnung ist, wird das Plentysystem eine Bestellung erstellen und der Kunde wird zur Bestellstatus-Seite weitergeleitet.

## Workflow-Beschreibung
### Protokollierung
Um das Logs of Plentymarkets System zu sehen, navigieren Sie zur Seite `Daten`->`Log`.
Dort sehen Sie das gesamte Logs of Plentymarkets System.
Normalerweise zeigt das heidelpayMGW-Plugin nur Protokolle auf der Ebene `Fehler` an.
Zusätzlich können Sie `debug`-Level-Logs aktivieren um zusätzliche Informationen zu erhalten.
Um `debug`-Level-Logs zu aktivieren, drücken Sie `Configure logs` in der oberen mittleren Sektion der `Log`-Seite.
Im geöffneten Popup-Fenster wählen Sie das Plugin `HeidelpayMGW`, markieren Sie die Checkbox `Aktiv`, 
wählen Sie die Dauer, wie lange diese Konfiguration aktiv sein soll und wählen Sie `Debug` aus der Liste der `Log-Ebene`.

### Externe Bestellnummer
Wenn eine Bestellung mit heidelpayMGW plugin erstellt wird, wird die Bestellung mit einer `Externen Bestellnummer` versehen. 
Diese Nummer ist die Bestell-ID in Ihrem hIP (heidelpay Intelligence Platform). 
Um diese Nummer im Plentymarkets System zu finden, navigieren Sie zur Seite `Orders bearbeiten`. 
Suchen Sie nach der mit dem heidelpayMGW-Plugin erstellten Order und öffnen Sie diese. 
In der geöffneten Order gehen Sie zum Reiter `Einstellungen` und dort sehen Sie `Ext. Ordernummer`.

### Status einer Zahlung
Um zu sehen, welchen Status eine Zahlung für einen bestimmten Auftrag hat, öffnen Sie diesen Auftrag. Navigieren Sie zur Registerkarte `Zahlung`.
Dort sehen Sie, welchen Status die Zahlung im Moment hat. Wenn sich die Zahlung im heidelpay-System ändert, erhält das Plugin das Ereignis, dass sich die Zahlung geändert hat und ändert den Status einer Zahlung im Plentymarkets-System automatisch.

### Rechnung erstellen
Um ein Rechnungsdokument für die Bestellung zu erstellen, navigieren Sie zur Seite `Bestellungen bearbeiten`.
Suchen Sie nach der Bestellung, für die Sie eine Rechnung erstellen möchten, und klicken Sie darauf. In der geöffneten Bestellung gehen Sie zur Registerkarte `Quittungen`.
Wählen Sie aus der Liste `Quittung erstellen` die Option `Rechnung`. In dem neuen Fenster nehmen Sie gegebenenfalls Änderungen vor und drücken Sie die Schaltfläche "Speichern".
Danach werden Sie das erstellte Rechnungsdokument sehen. Für die Zahlungsmethoden der "Rechnung" werden die zusätzlichen Zahlungsinformationen automatisch zum Rechnungsdokument hinzugefügt.
> Wenn Sie aus irgendeinem Grund nicht in der Lage sind, ein Rechnungsdokument zu erstellen, müssen Sie Ihre Rechnungsvorlage überprüfen.
>Gehen Sie zu `System`->`Kunde`->`{Ihr Geschäft}`->`Standorte`->`Deutschland (Standard)`->`Dokumente`->`Rechnung`, um dies zu tun.

### Lieferschein erstellen
Um ein Lieferscheindokument für die Bestellung zu erstellen, navigieren Sie zur Seite `Bestellungen bearbeiten`.
Suchen Sie nach der Bestellung, für die Sie ein Dokument erstellen möchten, und klicken Sie darauf.
In der geöffneten Bestellung gehen Sie zur Registerkarte `Quittungen`. Wählen Sie aus der Liste `Quittung erstellen` die Option `Lieferschein`.
In dem neuen Fenster nehmen Sie gegebenenfalls Änderungen vor und drücken Sie die Schaltfläche "Speichern". Danach werden Sie das erstellte Dokument sehen.
> Wenn Sie aus irgendeinem Grund nicht in der Lage sind, ein Lieferscheindokument zu erstellen, müssen Sie Ihre Dokumentvorlage überprüfen.
>Gehen Sie zu `System`->`Kunde`->`{Ihr Geschäft}`->`Standorte`->`Deutschland (Standard)`->`Dokumente`->`Lieferschein`, um dies zu tun.

### Rechnungszahlung abschließen
> Dieser Abschnitt ist relevant für `versicherte Rechnungskauf` Zahlungsmethoden

Um die Versicherung einer Zahlung zu starten, müssen Sie eine Finalize Transaktion auslösen. Hierfür gibt es zwei Möglichkeiten:
* Sie können dies in Ihrem hIP-Konto (heidelpay Intelligence Platform) tun.
* Sie können dies tun, indem Sie eine Ereignisprozedur (Finalize transaction (HeidelpayMGW)) im Shop-Backend erstellen (siehe [Ereignisprozedur erstellen](#Ereignisprozedur erstellen))
> Empfehlung ist, ein Ereignisverfahren zu erstellen, wenn die Rechnung erstellt wird
* Mit der Finalisierung beginnt die Versicherungsperiode, in der der Kunde den Gesamtbetrag der Bestellung zu zahlen hat.
* Die Versicherungsperiode wird in Ihrem Vertrag mit heidelpay festgelegt.
* Sobald der Kunde den Gesamtbetrag bezahlt hat, erscheint eine Quittungstransaktion (REC) innerhalb der HIP und wird an die PushUrl Ihres Shops geschickt.
* Das Plugin aktualisiert dann die Zahlungen der entsprechenden Bestellung.

### Zahlung stornieren
Um die Zahlung zu stornieren, müssen Sie im Shop-Backend eine Ereignisprozedur (Transaktion stornieren (HeidelpayMGW)) erstellen (siehe [Ereignisprozedur erstellen](#Ereignisprozedur erstellen).
> Es wird empfohlen, eine Ereignisprozedur zu erstellen, wenn ein Kredit-Knotenpunkt-Dokument erstellt wird (siehe [#Ereignisprozedur erstellen](#Ereignisprozedur erstellen)).
Der übliche Arbeitsablauf würde darin bestehen, zur ursprünglichen Bestellung zu navigieren. Im Reiter `Übersicht` sehen Sie eine Listbox mit dem Namen `Gutschrift...`. 
>Wählen Sie aus der Liste `von einzelnen Positionen` oder `von allen Positionen`. Im geöffneten Popup wählen Sie die Positionen aus, die Sie erstatten möchten. Drücken Sie die Schaltfläche "Speichern".
>Plentymarkets wird einen neuen Gutschriftsauftrag erstellen. Navigieren Sie zum Reiter `Quittungen` und wählen Sie aus der Liste `Quittung erstellen` die Option `Gutschrift`. 
>Passen Sie bei Bedarf die Einstellungen an und drücken Sie `Speichern`. Wenn die Ereignisprozedur so konfiguriert ist, dass sie beim Generieren einer Gutschrift ausgelöst wird, sendet das Plugin den Rückerstattungsbetrag an heidelpay API.

> Wenn die Gutschriftsbestellung eine teilweise Stornierung der ursprünglichen Bestellung ist, zieht das Plugin die Versandkosten vom Gesamtbetrag der Gutschriftsbestellung ab, wenn es Daten an HeidelpayMGW sendet.

> Wenn der Gutschriftsauftrag eine vollständige Stornierung des ursprünglichen Kundenauftrags darstellt, 
>sendet das Plugin den Gesamtbetrag des Gutschriftsauftrags an HeidelpayMGW. 
>Wenn Sie die Versandkosten nicht in die vollständige Stornierung mit einbeziehen möchten, können Sie Credit Note Order öffnen, zum Reiter `Settings` navigieren und die Versandkosten manuell entfernen.
>Wenn Sie möchten, dass bei Gutschriftbestellungen die Versandkosten niemals in der ursprünglichen Bestellung enthalten sind,
>können Sie zu `Setup`->`Bestellungen`->`Einstellungen` navigieren und den Wert `Versandkosten in Gutschrift einbeziehen` in `Nein` ändern.

### Ereignisprozedur erstellen
Um eine neue Ereignisprozedur hinzuzufügen, müssen Sie diese Schritte durchführen:
* Gehen Sie zu `Einrichtung`->`Aufträge`->`Ereignisse`
* Drücken Sie unten auf die Schaltfläche Ereignisprozedur hinzufügen
* Benennen Sie in der Dialogbox Ihre Konfiguration und wählen Sie, wann dieses Ereignis ausgelöst werden soll, z.B. ``Auftragsänderung`->`Statusänderung`
* Drücken Sie Speichern
* Wenn der Modalmodus geschlossen wird, kreuzen Sie das Kontrollkästchen `Aktiv` an und fügen Sie im Abschnitt `Prozeduren` die Prozedur hinzu, die das gefeuerte Ereignis behandeln soll. In diesem Fall `Autorisierungsgebühr (heidelpay)`
* Danach speichern Sie Ihre Konfiguration, indem Sie oben auf die Schaltfläche `Save` klicken.

### Direkte Gebühr
Für diese Transaktion sind keine zusätzlichen Schritte erforderlich. Das Bankkonto des Kunden wird direkt belastet und Plentymarkets Order wird automatisch mit dem bezahlten Betrag aktualisiert.

### Autorisieren und belasten
Bei dieser Transaktionsart wird das Geld nicht direkt vom Kunden abgebucht, so dass Sie den reservierten Betrag letzteren belasten müssen. Dies kann auf zwei Arten erfolgen:
* Sie können dies tun, indem Sie den Lieferschein im Shop-Backend erstellen (siehe [Lieferschein erstellen](#Lieferschein erstellen))
* Sie können dies tun, indem Sie eine benutzerdefinierte Ereignisprozedur innerhalb Plentymarkets verwenden (siehe [Ereignisprozedur erstellen](#Ereignisprozedur erstellen))

### Zahlungsmethoden für Rechnungen
> _Rechnung und Rechnung gesichert B2C_ ist nur unter den folgenden Bedingungen verfügbar:
> 1. das Land ist entweder Deutschland oder Österreich
> 2. Die Adresse gehört nicht zu einer Firma

> _Rechnungsgesicherte B2B_ ist nur unter den folgenden Bedingungen verfügbar:
> 1. das Land ist entweder Deutschland oder Österreich
> 2. Die Adresse gehört zu einer Firma

### Alle Zahlungsmethoden
* Alle Zahlungsmethoden fügen `shortId` (die ID der Transaktion, die zur Zahlung geführt hat, d.h. Quittung, Abbuchung oder Erfassung) zur Auftragszahlung hinzu (siehe [Status einer Zahlung](#Status einer Zahlung)).
* Im Falle eines Fehlers wird eine Fehlermeldung in der Auftragsnotiz und in den Plentymarkets-Protokollen hinzugefügt (siehe [Protokollierung](#protokollierung)).
