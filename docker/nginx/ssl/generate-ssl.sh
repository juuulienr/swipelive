#!/bin/bash

# Script pour générer des certificats SSL auto-signés pour le développement
# Usage: ./generate-ssl.sh

SSL_DIR="$(cd "$(dirname "$0")" && pwd)"
CERT_FILE="$SSL_DIR/swipelive.crt"
KEY_FILE="$SSL_DIR/swipelive.key"

echo "Génération des certificats SSL auto-signés pour SwipeLive..."

# Générer la clé privée
openssl genrsa -out "$KEY_FILE" 2048

# Générer le certificat auto-signé valide pour 127.0.0.1 et localhost
openssl req -new -x509 -key "$KEY_FILE" -out "$CERT_FILE" -days 365 \
    -subj "/C=FR/ST=France/L=Paris/O=SwipeLive/CN=localhost" \
    -addext "subjectAltName=IP:127.0.0.1,DNS:localhost,DNS:*.localhost"

# Définir les permissions appropriées
chmod 600 "$KEY_FILE"
chmod 644 "$CERT_FILE"

echo "Certificats générés avec succès :"
echo "  - Certificat: $CERT_FILE"
echo "  - Clé privée: $KEY_FILE"
echo ""
echo "Note: Ces certificats sont auto-signés et ne seront pas reconnus par défaut par les navigateurs."
echo "Vous devrez accepter l'avertissement de sécurité lors de la première connexion."

