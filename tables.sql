CREATE TABLE utilisateurs
(
    id_utilisateur INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nom VARCHAR(20),
    prenom VARCHAR(20),
    login VARCHAR(20) NOT NULL UNIQUE,
    hpass BINARY(64),
    privileges INT
);

CREATE TABLE article
(
    id_article INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nom VARCHAR(20),
    code_produit VARCHAR(20) NOT NULL UNIQUE,
    description VARCHAR(100)
);

CREATE TABLE entrepot
(
    id_entrepot INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    nom VARCHAR(10) UNIQUE
);

CREATE TABLE allee
(
    id_allee INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    allee VARCHAR(1) UNIQUE
);

CREATE TABLE travee
(
    id_travee INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    travee VARCHAR(2) UNIQUE
);

CREATE TABLE niveau
(
    id_niveau INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    niveau VARCHAR(2) UNIQUE
);

CREATE TABLE alveole
(
    id_alveole INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    alveole VARCHAR(2) UNIQUE
);


CREATE TABLE entrepot_site
(
    id_site INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    id_allee INT  NOT NULL,
    id_travee INT NOT NULL,
    id_niveau INT NOT NULL,
    id_alveole INT NOT NULL,
    id_entrepot INT NOT NULL,
    FOREIGN KEY (id_entrepot) REFERENCES entrepot(id_entrepot),
    FOREIGN KEY (id_allee) REFERENCES allee(id_allee),
    FOREIGN KEY (id_travee) REFERENCES travee(id_travee),
    FOREIGN KEY (id_niveau) REFERENCES niveau(id_niveau),
    FOREIGN KEY (id_alveole) REFERENCES alveole(id_alveole)
    CONSTRAINT UC_pos UNIQUE (id_allee, id_travee, id_niveau,id_alveole)
);

CREATE TABLE stock
(
    id_stock INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    quantity INT NOT NULL,
    id_site INT NOT NULL UNIQUE,
    id_article INT NOT NULL,
    FOREIGN KEY (id_site) REFERENCES entrepot_site(id_site),
    FOREIGN KEY (id_article) REFERENCES article(id_article),
    constraint co_quantity check (quantity > 0)
);

CREATE TABLE `transactions` (
  `id_transaction` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `id_article` int(11) NOT NULL,
  `id_site` int(11) NOT NULL,
  `delta` int(11) NOT NULL,
  `estampille` datetime NOT NULL,
  PRIMARY KEY (`id_transaction`),
  KEY `id_utilisateur` (`id_utilisateur`),
  KEY `id_article` (`id_article`),
  KEY `id_site` (`id_site`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`),
  CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`id_article`) REFERENCES `article` (`id_article`),
  CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`id_site`) REFERENCES `entrepot_site` (`id_site`)
);

CREATE VIEW joint_stock AS SELECT S.id_stock, S.quantity, S.id_site, A.*, E.id_entrepot, E.nom as "entrepot", AL.*, T.*, N.*, AV.*
	FROM stock S
	INNER JOIN article A ON S.id_article = A.id_article
	INNER JOIN entrepot_site ES ON S.id_site = ES.id_site
        INNER JOIN entrepot E ON E.id_entrepot = ES.id_entrepot
	INNER JOIN allee AL ON AL.id_allee = ES.id_allee
        INNER JOIN travee T ON T.id_travee = ES.id_travee
        INNER JOIN niveau N ON N.id_niveau = ES.id_niveau
        INNER JOIN alveole AV ON AV.id_alveole = ES.id_alveole;

DELIMITER //
CREATE OR REPLACE PROCEDURE addToStock (OUT mid_site INT, OUT mid_article INT,
             IN mcode_produit VARCHAR(10),
             IN mentrepot VARCHAR(10), IN mallee VARCHAR(10), IN mtravee VARCHAR(10), IN mniveau VARCHAR(10), IN malveole VARCHAR(10),
             IN mquantity INT)
    MODIFIES SQL DATA
    BEGIN
        DECLARE vid_article INT;
        DECLARE vid_site INT;
        DECLARE vid_entrepot INT;
        DECLARE vid_allee INT;
        DECLARE vid_travee INT;
        DECLARE vid_niveau INT;
        DECLARE vid_alveole INT;
        DECLARE vid_stock INT;
        DECLARE vcur_qt INT;

        SELECT id_article, id_article INTO vid_article, mid_article FROM article WHERE article.code_produit = mcode_produit LIMIT 1;
        SELECT id_stock, id_site INTO vid_stock, vid_site FROM joint_stock AS J WHERE J.id_article = vid_article AND J.entrepot = mentrepot AND J.allee = mallee AND J.travee = mtravee AND J.niveau = mniveau AND J.alveole = malveole LIMIT 1;
        IF ISNULL(vid_stock) THEN
            SELECT id_entrepot INTO vid_entrepot FROM entrepot as T WHERE T.nom = mentrepot;
            SELECT id_allee INTO vid_allee FROM allee as T WHERE T.allee = mallee;
            SELECT id_travee INTO vid_travee FROM travee as T WHERE T.travee = mtravee;
            SELECT id_niveau INTO vid_niveau FROM niveau as T WHERE T.niveau = mniveau;
            SELECT id_alveole INTO vid_alveole FROM alveole as T WHERE T.alveole = malveole;
            SELECT id_site INTO vid_site FROM entrepot_site WHERE id_entrepot = vid_entrepot AND id_allee = vid_allee AND id_travee = vid_travee AND id_niveau = vid_niveau AND id_alveole = vid_alveole;
            IF ISNULL(vid_site) THEN
                INSERT INTO entrepot_site(id_allee, id_travee, id_niveau, id_alveole, id_entrepot) VALUES (vid_allee, vid_travee, vid_niveau, vid_alveole, vid_entrepot);
                SELECT LAST_INSERT_ID() INTO vid_site;
            end if;
            INSERT INTO stock(quantity, id_site, id_article) VALUES (mquantity, vid_site, vid_article);
        ELSE
            SELECT quantity INTO vcur_qt FROM stock WHERE id_stock = vid_stock LIMIT 1;
            IF vcur_qt + mquantity = 0 THEN
                DELETE FROM stock WHERE id_stock = vid_stock;
            ELSE
                UPDATE stock AS S SET S.quantity = S.quantity + mquantity WHERE S.id_stock = vid_stock;
            end if;
        end if;
        SELECT vid_site INTO mid_site;
    end; //

DELIMITER ;
