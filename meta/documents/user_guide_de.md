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
+ Verwenden Sie für die unten beschriebenen Konfigurationsschritte ein Test KeyPair,um zu das Plugin im Sandboxmodus zu testen.
> Ein Test-KeyPair können Sie über den Support (support@heidelpay.com) anfordern.
+ Sobald Sie mit der Konfiguration zufrieden sind, können Sie das Schlüsselpaar durch eines für den Livemodus ersetzen, um das Modul produktiv einzusetzen 

## Konfiguration
### Grundkonfiguration
+ Wählen Sie das entsprechende Plugin-Set und dann das Plugin "heidelpay merchant gateway" aus der Pluginliste aus, um zur Konfigurationsübersicht zu wechseln.
+ Wechseln Sie zu der Registerkarte `Standard-Container-Verknüpfung`, wählen Sie alle Elemente in der Liste aus und speichern Sie die Konfiguration.
+ Für die Plugin-Konfiguration gehen Sie bitte zu `Einrichtung`->`Aufträge`->`Zahlung`->`Plugins`->`Heidelpay`.

## Plugin-Einstellungen
####### Öffentlicher / Privater Schlüssel
Zum Erstellen von Zahlungsarten durch den Browser ist ein öffentlicher Schlüssel erforderlich.
Der private Schlüssel ist für die Authentifizierung auf dem heidelpay-Server zur Durchführung von Transaktionen erforderlich.

Der erste Buchstabe des jeweiligen Schlüssels bestimmt die Umgebung in der das Plugin arbeitet:
* `s` arbeitet im Testmodus gegen die Sandbox der API (es findet kein Transfer von Geldern statt)
* `p` identifiziert einen Schlüssel für den Produktionsmodus. Hiermit finden tatsächliche Transaktionen statt.

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
Damit wird ein Icon für die Zahlungsart definiert, das auf der Checkout-Seite zusätzlich zum Anzeigenamen angezeigt wird.
Wenn das Feld leer gelassen wird, wird das Standardsymbol verwendet.  
Voraussetzungen für die URL-Zeichenfolge:
* sie muss aus dem Internet erreichbar sein
* sie muss mit 'http://' oder 'https://' beginnen
* sie muss mit '.jpg', '.png' oder '.gif' enden

####### Transaktionsmodus (Kartenzahlung und Paypal)
Kartenzahlungen und Paypal können auf zwei verschiedene Arten verwendet werden:
* Direktbelastung: das Bankkonto des Kunden wird direkt belastet.
* Autorisieren und belasten: Zuerst wird eine Summe auf dem Konto des Kunden reserviert und Sie belasten diese Summe zu einem späteren Zeitpunkt.

## Handbuch
Für zusätzliche Informationen wie eine Workflow-Beschreibung oder der Erstellung von Ereignisprozeduren,
verweisen wir auf unser [Handbuch] (https://dev.heidelpay.com/user-guide-plentymarkets-mgw-plugin
                                    
)
