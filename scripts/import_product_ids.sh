#!/bin/bash

# Configuration du projet
PROJECT_PATH="/home/collection/www"

# Vérification des paramètres
if [ $# -eq 1 ]; then
    # Un fichier a été passé en paramètre
    FICHIER="$1"
    echo "Utilisation du fichier fourni en paramètre: $FICHIER"
    
    # Vérification que le fichier existe
    if [ ! -f "$FICHIER" ]; then
        echo "Erreur: Le fichier $FICHIER n'existe pas"
        exit 1
    fi
else
    # Aucun paramètre fourni, génération automatique du fichier
    echo "Génération du fichier product_ids.txt avec les IDs en échec..."
    
    # Obtenir la date actuelle au format DD_MM_YYYY
    DATE=$(date +%d_%m_%Y)
    echo "Date utilisée: $DATE"
    
    # Générer le fichier product_ids.txt depuis les logs
    grep -o 'Product([0-9]\+)' ${PROJECT_PATH}/storage/logs/worker_${DATE}.log | sed 's/Product(\([0-9]\+\))/\1/' | sort -n | uniq > ${PROJECT_PATH}/scripts/product_ids.txt
    
    FICHIER="${PROJECT_PATH}/scripts/product_ids.txt"
    echo "Fichier ${FICHIER} généré avec succès"
fi

# Aller dans le répertoire de l'application
cd ${PROJECT_PATH}

# Vérification finale du fichier
if [ ! -f "$FICHIER" ]; then
  echo "Fichier non trouvé : $FICHIER"
  exit 2
fi

echo "Début du traitement des IDs en échec..."
echo "Nombre d'IDs à traiter: $(wc -l < "$FICHIER")"

while IFS= read -r id
do
  # Ignore les lignes vides ou les commentaires
  if [ -z "$id" ] || echo "$id" | grep -q '^#'; then
    continue
  fi
  echo "Import de l'id : $id"
  php artisan gobelins:import:zetcom --all --limit=10 --offset=0 --objectIds="$id"
done < "$FICHIER"

echo "Traitement terminé" 