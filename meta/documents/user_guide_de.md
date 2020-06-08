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
* Dieses Plugin wurde für Plentymarkets 7 entwickelt.

## Versionshinweise
Dieses Modul basiert auf dem heidelpay php-sdk (https://github.com/heidelpay/heidelpayPHP).

## Installation
+ Bitte lesen Sie [plentyKnowledge] (https://knowledge.plentymarkets.com), um zu erfahren, wie man Plugins installiert.
+ Nachdem Sie die unten beschriebenen Konfigurationsschritte durchgeführt haben, sind Sie in der Lage, einige Tests im Staging-Modus durchzuführen.
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

> Wenn die `Speichern`-Taste gedrückt wird, registriert das heidelpay Plugin Webhooks mit den eingetragenen Schlüsseln.
> Die Webhooks dienen dazu, Änderungen an den Zahlungen mit dem Plentymarkets System zu synchronisieren.
> Beispielsweise die Aktualisierung des Zahlungsstatus, sobald eine Rechnung von dem Kunden bezahlt wurde.

####### Parameter der Zahlungsart
###### Aktiv
Aktivieren Sie das Häckchen um die Zahlungsart auf der Checkout-Seite zur Verfügung zu stellen.

###### Anzeigename
Der Name der Zahlungsart wird auf der Checkout-Seite angezeigt.  
Ein Standardname wird angezeigt, wenn die Eingabe leer gelassen wird.

####### Min-/Max-Summe
Die Zahlungsart steht nur dann zur Verfügung, wenn der Warenkorb eine Summe zwischen diesen Werten aufweist.
Wenn Sie einen dieser Werte auf 0 setzen, wird die entsprechende Beschränkung deaktiviert.

####### URL zum Zahlungssymbol
Damit wird ein Symbol für die Zahlungsart definiert, das auf der Checkout-Seite zusätzlich zum Anzeigenamen angezeigt wird.
Wenn das Feld leer gelassen wird, wird das Standardsymbol verwendet.  
Voraussetzungen für die url-Zeichenfolge:
* sie muss aus dem Internet erreichbar sein
* es muss mit 'http://' oder 'https://' beginnen
* es muss mit '.jpg', '.png' oder '.gif' enden

####### Transaktionsmodus (Kartenzahlung und Paypal)
Kartenzahlungen und Paypal können auf zwei verschiedene Arten verwendet werden:
* Direktbelastung: das Bankkonto des Kunden wird direkt belastet.
* Autorisieren und belasten: Zuerst reservieren Sie eine Summe auf dem Konto des Kunden und später belasten Sie diese Summe.

Wenn der Kunde eine Karte hat, die 3D-Sicherheit verwendet, wird er während des Bestellvorgangs auf eine Seite weitergeleitet, auf der er seine Zahlung autorisieren kann.
Wenn der Kunde aus irgendeinem Grund nicht in der Lage ist, eine Autorisierung vorzunehmen, wird er zurück zur Kasse umgeleitet.
Wenn die Autorisierung erfolgreich durchgeführt wurde, erstellt das Plentymarkets System eine Bestellung und der Kunde wird zur Bestellbestätigung-Seite weitergeleitet.

## Handbuch
Für zusätzliche Informationen wie eine Workflow-Beschreibung oder der Erstellung von Ereignisprozeduren,
verweisen wir auf unser [Handbuch] (https://dev.heidelpay.com)