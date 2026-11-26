"""
Base do motor de motion do Estúdio de vídeo: easing (no estilo GSAP),
desenho (cartões, pílulas, texto, brilho, ícones SVG) e o envelope de
entrada/saída de cada elemento.

Tudo é desenhado num espaço de 1080 de largura (a altura segue o formato);
a prévia só muda a escala do canvas. Compatível com skia-python 87.x
(servidor com glibc antigo) e 1xx.
"""
import math
import os
import re
from contextlib import contextmanager

import skia

# --- Easing ---------------------------------------------------------------


def clamp(v, a=0.0, b=1.0):
    return a if v < a else b if v > b else v


def fase(t, inicio, dur):
    """0 → 1 entre inicio e inicio+dur (preso nas pontas)."""
    if dur <= 0:
        return 1.0 if t >= inicio else 0.0
    return clamp((t - inicio) / dur)


def lin(p):
    return p


def out_cubic(p):
    return 1 - (1 - p) ** 3


def out_expo(p):
    return 1.0 if p >= 1 else 1 - 2 ** (-10 * p)


def in_out(p):
    return 4 * p ** 3 if p < 0.5 else 1 - (-2 * p + 2) ** 3 / 2


def out_back(p, s=1.7):
    p -= 1
    return 1 + (s + 1) * p ** 3 + s * p ** 2


def out_elastic(p):
    if p <= 0 or p >= 1:
        return clamp(p)
    return 2 ** (-10 * p) * math.sin((p * 10 - 0.75) * (2 * math.pi) / 3) + 1


def mix(a, b, p):
    return a + (b - a) * p


# --- Cores ----------------------------------------------------------------


def rgb(hexa):
    h = (hexa or "#FFFFFF").lstrip("#")
    if len(h) == 3:
        h = "".join(c * 2 for c in h)
    return int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16)


def cor(hexa, alfa=1.0):
    r, g, b = rgb(hexa)
    return skia.Color(r, g, b, int(clamp(alfa) * 255))


def luminancia(hexa):
    r, g, b = (c / 255 for c in rgb(hexa))
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


def misturar(h1, h2, p):
    a, b = rgb(h1), rgb(h2)
    return "#%02X%02X%02X" % tuple(int(mix(a[i], b[i], p)) for i in range(3))


# --- Pintura --------------------------------------------------------------


def tinta(c, estilo="fill", largura=0, desfoque=0, aa=True):
    p = skia.Paint(AntiAlias=aa, Color=c)
    if estilo == "stroke":
        p.setStyle(skia.Paint.kStroke_Style)
        p.setStrokeWidth(largura)
        p.setStrokeCap(skia.Paint.kRound_Cap)
        p.setStrokeJoin(skia.Paint.kRound_Join)
    if desfoque > 0:
        p.setMaskFilter(skia.MaskFilter.MakeBlur(skia.kNormal_BlurStyle, desfoque))
    return p


def imagem(c, img, x, y, w, h):
    """drawImageRect suave nas duas versões do skia-python (87 não tem SamplingOptions)."""
    destino = skia.Rect.MakeXYWH(x, y, w, h)
    if hasattr(skia, "SamplingOptions"):
        c.drawImageRect(img, destino, skia.SamplingOptions(skia.FilterMode.kLinear, skia.MipmapMode.kLinear))
    else:
        p = skia.Paint(AntiAlias=True)
        p.setFilterQuality(skia.kMedium_FilterQuality)
        c.drawImageRect(img, destino, p)


def rrect(x, y, w, h, r):
    return skia.RRect.MakeRectXY(skia.Rect.MakeXYWH(x, y, w, h), r, r)


class Estilo:
    """A 'casa' visual, derivada da identidade do cliente."""

    def __init__(self, ident):
        self.destaque = ident.get("destaque", "#F97316")
        self.texto = ident.get("texto", "#FFFFFF")
        self.primaria = ident.get("primaria", "#111827")
        self.fundo_marca = ident.get("fundo", "#0B0B0F")
        # Cartão escuro e neutro (lê bem sobre qualquer imagem), com a marca no acento.
        self.cartao = "#111113"
        self.cartao_borda = "#FFFFFF"
        self.suave = "#9A9AA2"  # texto secundário
        self.trilho = "#2A2A2F"


_DESFOQUES = {}


def desfocado(c, x, y, w, h, raio, sigma, hexa, alfa=1.0, traco=0.0):
    """
    Retângulo arredondado desfocado (sombra/brilho) desenhado a partir de um
    cache: o desfoque é o que mais pesa por quadro, e a forma não muda durante
    a animação — só a posição, a escala e o alfa (que vêm do canvas).
    """
    if alfa <= 0.003:
        return
    chave = (round(w), round(h), round(raio), round(sigma), hexa, round(traco, 1))
    img = _DESFOQUES.get(chave)
    m = int(sigma * 3 + traco + 4)
    if img is None:
        sw, sh = int(w) + 2 * m, int(h) + 2 * m
        s = skia.Surface(max(1, sw), max(1, sh))
        cv = s.getCanvas()
        cv.clear(skia.ColorTRANSPARENT)
        p = tinta(cor(hexa), "stroke" if traco else "fill", traco, desfoque=sigma)
        cv.drawRRect(rrect(m, m, w, h, raio), p)
        img = s.makeImageSnapshot()
        if len(_DESFOQUES) > 400:
            _DESFOQUES.clear()
        _DESFOQUES[chave] = img
    p = skia.Paint(AntiAlias=True)
    p.setAlphaf(clamp(alfa))
    if hasattr(skia, "SamplingOptions"):
        c.drawImage(img, x - m, y - m, skia.SamplingOptions(skia.FilterMode.kLinear), p)
    else:
        p.setFilterQuality(skia.kLow_FilterQuality)
        c.drawImage(img, x - m, y - m, p)


def cartao(c, x, y, w, h, raio, est, alfa=1.0, borda_cor=None, borda=2.0, brilho=0.0, fundo=None, sombra=True):
    """Cartão com sombra suave, fundo quase opaco, borda fina e brilho opcional na cor de acento."""
    r = rrect(x, y, w, h, raio)
    if sombra:
        desfocado(c, x, y + 18, w, h, raio, 34, "#000000", 0.55 * alfa)
    if brilho > 0:
        desfocado(c, x, y, w, h, raio, 18, borda_cor or est.destaque, 0.55 * brilho * alfa, borda + 10)
    c.drawRRect(r, tinta(cor(fundo or est.cartao, 0.93 * alfa)))
    # Reflexo no topo: dá volume sem parecer "template".
    c.save()
    c.clipRRect(r, doAntiAlias=True)
    grad = skia.GradientShader.MakeLinear(
        [skia.Point(x, y), skia.Point(x, y + min(h, 160))],
        [cor("#FFFFFF", 0.06 * alfa), cor("#FFFFFF", 0.0)],
    )
    p = skia.Paint(AntiAlias=True)
    p.setShader(grad)
    c.drawRect(skia.Rect.MakeXYWH(x, y, w, min(h, 160)), p)
    c.restore()
    c.drawRRect(r, tinta(cor(borda_cor or est.cartao_borda, (0.85 if borda_cor else 0.09) * alfa), "stroke", borda))


def brilho_radial(c, cx, cy, raio, hexa, alfa):
    p = skia.Paint(AntiAlias=True)
    p.setShader(skia.GradientShader.MakeRadial(skia.Point(cx, cy), raio, [cor(hexa, alfa), cor(hexa, 0.0)]))
    c.drawCircle(cx, cy, raio, p)


@contextmanager
def grupo(c, cx, cy, alfa=1.0, escala=1.0, dx=0.0, dy=0.0, giro=0.0):
    """Transforma um bloco inteiro (entrada/saída) em volta do seu centro."""
    c.save()
    if alfa < 0.999:
        c.saveLayerAlpha(None, int(clamp(alfa) * 255))
    c.translate(cx + dx, cy + dy)
    if giro:
        c.rotate(giro)
    if abs(escala - 1) > 1e-4:
        c.scale(escala, escala)
    c.translate(-cx, -cy)
    try:
        yield
    finally:
        if alfa < 0.999:
            c.restore()
        c.restore()


# --- Fontes e texto -------------------------------------------------------


class Fontes:
    def __init__(self, arquivos):
        self._tf = {}
        padrao = None
        for nome, caminho in (arquivos or {}).items():
            tf = skia.Typeface.MakeFromFile(caminho) if caminho and os.path.isfile(caminho) else None
            if tf is not None:
                self._tf[nome] = tf
                padrao = padrao or tf
        self._padrao = padrao or skia.Typeface.MakeDefault()

    def tf(self, nome):
        for n in (nome, "forte", "texto", "titulo"):
            if n in self._tf:
                return self._tf[n]
        return self._padrao

    def fonte(self, nome, tamanho):
        f = skia.Font(self.tf(nome), tamanho)
        f.setEdging(skia.Font.Edging.kAntiAlias)
        f.setSubpixel(True)
        return f


def largura(fonte, s, espaco=0.0):
    return fonte.measureText(s) + espaco * max(0, len(s) - 1)


def escrever(c, s, x, y, fonte, cr, alinhar="esq", espaco=0.0, brilho=None, sombra=0.0):
    """Texto na linha de base y. espaco = espaçamento extra entre letras (px)."""
    if not s:
        return 0.0
    w = largura(fonte, s, espaco)
    if alinhar == "centro":
        x -= w / 2
    elif alinhar == "dir":
        x -= w

    def pinta(p, ox=0.0, oy=0.0):
        if espaco:
            cx = x + ox
            for ch in s:
                c.drawString(ch, cx, y + oy, fonte, p)
                cx += fonte.measureText(ch) + espaco
        else:
            c.drawString(s, x + ox, y + oy, fonte, p)

    if sombra:
        pinta(tinta(cor("#000000", 0.55), desfoque=sombra), 0, sombra * 0.35)
    if brilho:
        pinta(tinta(brilho[0], desfoque=brilho[1]))
    pinta(skia.Paint(AntiAlias=True, Color=cr))
    return w


def quebrar(fonte, s, maxw):
    linhas, atual = [], ""
    for p in s.split():
        teste = (atual + " " + p).strip()
        if atual and fonte.measureText(teste) > maxw:
            linhas.append(atual)
            atual = p
        else:
            atual = teste
    if atual:
        linhas.append(atual)
    return linhas or [""]


def caber(fontes, nome, s, maxw, tamanho, minimo=18):
    """Maior fonte (até tamanho) em que s cabe em maxw."""
    while tamanho > minimo:
        f = fontes.fonte(nome, tamanho)
        if f.measureText(s) <= maxw:
            return f
        tamanho -= 2
    return fontes.fonte(nome, minimo)


# --- Ícones (SVG) ---------------------------------------------------------


_NUM = re.compile(r"[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?")


def normalizar_d(d):
    """
    Separa os números de um path SVG. Os ícones minificados usam arcos
    compactos ("a12 12 0 01.5-2": as flags 0 e 1 grudadas) que o leitor do
    skia 87 não entende — o ícone sumia.
    """
    saida, i, n, cmd, idx = [], 0, len(d), None, 0
    while i < n:
        ch = d[i]
        if ch.isalpha() and ch not in "eE":
            cmd, idx = ch, 0
            saida.append(ch)
            i += 1
        elif ch in " ,\t\r\n":
            i += 1
        elif cmd in ("a", "A") and idx % 7 in (3, 4) and ch in "01":
            saida.append(ch)
            i += 1
            idx += 1
        else:
            m = _NUM.match(d, i)
            if not m:
                i += 1
                continue
            saida.append(m.group(0))
            i = m.end()
            idx += 1
    return " ".join(saida)


class Icones:
    """
    Marcas: Simple Icons (CC0, ~3.400 logos). Genéricos: Lucide (ISC, ~2.400).
    Apps que não estão no Simple Icons (Adobe, Microsoft, CapCut, Canva…)
    viram bloco com sigla nas cores da marca — como o ícone oficial do app.
    """

    APPS = {
        "premiere": ("Pr", "#00005B", "#9999FF"), "adobepremierepro": ("Pr", "#00005B", "#9999FF"),
        "aftereffects": ("Ae", "#00005B", "#D291FF"), "photoshop": ("Ps", "#001E36", "#31A8FF"),
        "illustrator": ("Ai", "#330000", "#FF9A00"), "lightroom": ("Lr", "#001E36", "#31A8FF"),
        "indesign": ("Id", "#49021F", "#FF3366"), "audition": ("Au", "#00005B", "#9999FF"),
        "capcut": ("CC", "#000000", "#FFFFFF"), "canva": ("Ca", "#00C4CC", "#FFFFFF"),
        "chatgpt": ("GPT", "#10A37F", "#FFFFFF"), "openai": ("AI", "#000000", "#FFFFFF"),
        "excel": ("X", "#107C41", "#FFFFFF"), "word": ("W", "#185ABD", "#FFFFFF"),
        "powerpoint": ("P", "#C43E1C", "#FFFFFF"), "teams": ("T", "#5059C9", "#FFFFFF"),
        "finalcut": ("FC", "#1C1C1E", "#E8E8ED"), "inshot": ("IS", "#FF3D58", "#FFFFFF"),
        "vn": ("VN", "#111111", "#FFFFFF"), "imovie": ("iM", "#6E3CFF", "#FFFFFF"),
    }

    def __init__(self, pasta, fontes):
        self.marcas = os.path.join(pasta or "", "marcas")
        self.lucide = os.path.join(pasta or "", "lucide")
        self.fontes = fontes
        self._cache = {}
        self._hex = {}
        dados = os.path.join(pasta or "", "marcas.json")
        if os.path.isfile(dados):
            import json
            try:
                for item in json.load(open(dados, encoding="utf-8")):
                    self._hex[item["slug"]] = "#" + item["hex"]
            except Exception:
                pass

    def cor_marca(self, slug):
        return self._hex.get(slug)

    def existe(self, tipo, nome):
        pasta = self.marcas if tipo == "marca" else self.lucide
        return bool(nome) and os.path.isfile(os.path.join(pasta, nome + ".svg"))

    def _dom(self, tipo, nome, hexa, traco):
        chave = (tipo, nome, hexa, traco)
        if chave in self._cache:
            return self._cache[chave]
        pasta = self.marcas if tipo == "marca" else self.lucide
        arq = os.path.join(pasta, (nome or "") + ".svg")
        dom = None
        if os.path.isfile(arq):
            svg = open(arq, encoding="utf-8").read()
            svg = re.sub(r"<!--.*?-->", "", svg, flags=re.S)
            svg = re.sub(r'\sd="([^"]*)"', lambda m: ' d="%s"' % normalizar_d(m.group(1)), svg)
            if tipo == "marca":
                # Cor em cada <path>: o SVG do skia 87 não herda o fill do <svg>.
                svg = svg.replace("<svg ", '<svg fill="%s" ' % hexa, 1).replace("<path ", '<path fill="%s" ' % hexa)
            else:
                svg = svg.replace("currentColor", hexa)
                svg = re.sub(r'stroke-width="[\d.]+"', 'stroke-width="%s"' % traco, svg)
            dom = skia.SVGDOM.MakeFromStream(skia.MemoryStream(svg.encode("utf-8"), True))
        self._cache[chave] = dom
        return dom

    def desenhar(self, c, tipo, nome, cx, cy, tamanho, hexa, traco=2.0):
        """Ícone centrado em (cx, cy). Devolve False se não existe."""
        dom = self._dom(tipo, nome, hexa, traco)
        if dom is None:
            return False
        c.save()
        c.translate(cx - tamanho / 2, cy - tamanho / 2)
        c.scale(tamanho / 24.0, tamanho / 24.0)
        dom.setContainerSize(skia.Size(24, 24))
        dom.render(c)
        c.restore()
        return True

    def imagem_logo(self, caminho):
        chave = ("img", caminho)
        if chave not in self._cache:
            self._cache[chave] = skia.Image.open(caminho) if caminho and os.path.isfile(caminho) else None
        return self._cache[chave]

    def cor_media(self, caminho):
        """Cor média (saturada) de uma imagem: o brilho em volta do logo baixado."""
        img = self.imagem_logo(caminho)
        if img is None:
            return None
        s = skia.Surface(8, 8)
        imagem(s.getCanvas(), img, 0, 0, 8, 8)
        import numpy as np
        px = np.zeros((8, 8, 4), dtype=np.uint8)
        s.readPixels(skia.ImageInfo.Make(8, 8, skia.kRGBA_8888_ColorType, skia.kPremul_AlphaType), px)
        melhor, nota = None, -1.0
        for linha in px:
            for p in linha:
                r, g, b = int(p[0]), int(p[1]), int(p[2])
                sat = max(r, g, b) - min(r, g, b)
                if sat > nota:
                    melhor, nota = (r, g, b), sat
        return "#%02X%02X%02X" % melhor if melhor else None

    def app(self, c, it, cx, cy, tamanho, est):
        """
        Ícone de app/marca a partir do item do plano:
          imagem + forma=app  → ícone oficial (App Store) recortado no "squircle" do iOS;
          imagem + forma=logo → logo sobre placa clara;
          logo (Simple Icons) → vetor na cor da marca;
          senão → bloco com a sigla nas cores do app.
        """
        it = it or {}
        img = self.imagem_logo(it.get("imagem"))
        if img is not None:
            lado = tamanho * 1.12
            x, y = cx - lado / 2, cy - lado / 2
            if it.get("forma") == "app":
                c.save()
                c.clipRRect(rrect(x, y, lado, lado, lado * 0.225), True)
                imagem(c, img, x, y, lado, lado)
                c.restore()
                c.drawRRect(rrect(x, y, lado, lado, lado * 0.225), tinta(cor("#FFFFFF", 0.12), "stroke", 2))
            else:
                c.drawRRect(rrect(x, y, lado, lado, lado * 0.225), tinta(cor("#F4F4F5")))
                s = lado * 0.78 / max(img.width(), img.height())
                iw, ih = img.width() * s, img.height() * s
                imagem(c, img, cx - iw / 2, cy - ih / 2, iw, ih)
            return
        sigla, fundo, letra = it.get("sigla"), it.get("fundo"), it.get("cor")
        nome = re.sub(r"[^a-z0-9]", "", (it.get("logo") or "").lower())
        if nome in self.APPS and not sigla:
            sigla, fundo, letra = self.APPS[nome]
        if not sigla and self.existe("marca", nome):
            h = self.cor_marca(nome) or est.texto
            if luminancia(h) < 0.12:  # logo preto some no cartão escuro
                h = "#FFFFFF"
            self.desenhar(c, "marca", nome, cx, cy, tamanho, h)
            return
        sigla = sigla or (nome[:2].title() if nome else "?")
        lado = tamanho * 1.08
        c.drawRRect(rrect(cx - lado / 2, cy - lado / 2, lado, lado, lado * 0.22), tinta(cor(fundo or est.destaque)))
        if (fundo or "").upper() in ("#000000", "#111111", "#1C1C1E"):
            c.drawRRect(rrect(cx - lado / 2, cy - lado / 2, lado, lado, lado * 0.22), tinta(cor("#FFFFFF", 0.18), "stroke", 2))
        f = caber(self.fontes, "forte", sigla, lado * 0.8, lado * (0.5 if len(sigla) <= 2 else 0.36))
        escrever(c, sigla, cx, cy + f.getSize() * 0.36, f, cor(letra or "#FFFFFF"), "centro")


# --- Elemento -------------------------------------------------------------


class Elemento:
    """
    Um elemento na linha do tempo. t é o tempo do vídeo (s); lt, o tempo
    local (s desde o início do elemento). As subclasses implementam desenhar().
    """

    entrada = 0.45
    saida = 0.28

    def __init__(self, dados, cena):
        self.d = dados
        self.cena = cena
        self.est = cena.est
        self.f = cena.fontes
        self.ic = cena.icones
        self.inicio = float(dados["inicio"])
        self.duracao = max(0.3, float(dados["duracao"]))
        self.fim = self.inicio + self.duracao
        self.W = cena.W
        self.H = cena.H
        self.preparar()

    def preparar(self):
        pass

    def ativo(self, t):
        return self.inicio - 0.001 <= t <= self.fim

    def pe(self, lt):
        return fase(lt, 0, self.entrada)

    def ps(self, lt):
        return fase(lt, self.duracao - self.saida, self.saida)

    def centro(self, padrao="topo", altura=0):
        """Centro vertical pela posição pedida (respeita a zona segura dos apps)."""
        pos = self.d.get("posicao") or padrao
        if isinstance(pos, list) and len(pos) >= 2:
            return self.W * float(pos[0]), self.H * float(pos[1])
        y = {"topo": 0.25, "centro": 0.45, "baixo": 0.66}.get(str(pos).split("_")[0], 0.25) * self.H
        x = self.W / 2
        if str(pos).endswith("_esquerda"):
            x = self.W * 0.32
        elif str(pos).endswith("_direita"):
            x = self.W * 0.68
        # Nunca invade os 8% de cima nem os 22% de baixo.
        meia = altura / 2
        y = clamp(y, self.H * 0.08 + meia, self.H * 0.78 - meia) if altura else y
        return x, y

    def t_item(self, item, i, base, passo):
        """Tempo de um item: o 't' resolvido (palavra-gatilho) ou em sequência."""
        t = item.get("t") if isinstance(item, dict) else None
        return float(t) if t is not None else self.inicio + base + i * passo

    def quadro(self, c, t):
        lt = t - self.inicio
        self.desenhar(c, lt, self.pe(lt), self.ps(lt), t)

    def desenhar(self, c, lt, pe, ps, t):
        raise NotImplementedError


class Cena:
    def __init__(self, pedido):
        self.W = 1080
        self.H = int(round(1080 * pedido["altura"] / pedido["largura"]))
        self.est = Estilo(pedido.get("identidade", {}))
        self.fontes = Fontes(pedido.get("fontes", {}))
        self.icones = Icones(pedido.get("icones"), self.fontes)
        self.logo = pedido.get("logo")
        self._logo_img = None

    def imagem_logo(self):
        if self._logo_img is None and self.logo and os.path.isfile(self.logo):
            self._logo_img = skia.Image.open(self.logo)
        return self._logo_img
