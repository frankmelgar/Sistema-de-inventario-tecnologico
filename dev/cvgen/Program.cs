using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.Text;

var workspace = @"C:\xampp\htdocs\HOLAMUNDO";
var inputImage = @"C:\Users\DELL\Downloads\WhatsApp Image 2026-04-08 at 19.18.14.jpeg";
var outputDir = Path.Combine(workspace, "documents", "cv");
Directory.CreateDirectory(outputDir);

var photoPath = Path.Combine(outputDir, "profile-photo-bw.png");
var cvEsImage = Path.Combine(outputDir, "cv-es-page.jpg");
var cvEnImage = Path.Combine(outputDir, "cv-en-page.jpg");
var pdfPath = Path.Combine(outputDir, "cv-bilingual-es-en.pdf");

using var processedPhoto = PreparePortrait(inputImage);
processedPhoto.Save(photoPath, ImageFormat.Png);

using var cvEs = BuildSpanishPage(processedPhoto);
cvEs.Save(cvEsImage, ImageFormat.Jpeg);

using var cvEn = BuildEnglishPage(processedPhoto);
cvEn.Save(cvEnImage, ImageFormat.Jpeg);

WritePdfFromJpegs(
    pdfPath,
    new List<string> { cvEsImage, cvEnImage },
    new List<(int Width, int Height)> { (2480, 3508), (2480, 3508) }
);

Console.WriteLine(photoPath);
Console.WriteLine(cvEsImage);
Console.WriteLine(cvEnImage);
Console.WriteLine(pdfPath);

static Bitmap PreparePortrait(string imagePath)
{
    using var original = new Bitmap(imagePath);
    var croppedBars = CropBlackBars(original);
    var cleaned = RemoveLightBackground(croppedBars);
    croppedBars.Dispose();
    var grayscale = ToGrayscale(cleaned);
    cleaned.Dispose();
    return CenterPortrait(grayscale);
}

static Bitmap CropBlackBars(Bitmap source)
{
    int top = 0;
    int bottom = source.Height - 1;

    bool IsDarkRow(int y)
    {
        long total = 0;
        for (int x = 0; x < source.Width; x += 8)
        {
            var c = source.GetPixel(x, y);
            total += (c.R + c.G + c.B) / 3;
        }
        var avg = total / Math.Max(1, source.Width / 8);
        return avg < 15;
    }

    while (top < source.Height && IsDarkRow(top))
    {
        top++;
    }
    while (bottom > top && IsDarkRow(bottom))
    {
        bottom--;
    }

    var rect = new Rectangle(0, top, source.Width, bottom - top + 1);
    return source.Clone(rect, PixelFormat.Format24bppRgb);
}

static Bitmap RemoveLightBackground(Bitmap source)
{
    var result = new Bitmap(source.Width, source.Height, PixelFormat.Format24bppRgb);
    using var g = Graphics.FromImage(result);
    g.DrawImage(source, 0, 0);

    var visited = new bool[source.Width, source.Height];
    var queue = new Queue<Point>();

    void TrySeed(int x, int y)
    {
        if (x >= 0 && x < source.Width && y >= 0 && y < source.Height)
        {
            queue.Enqueue(new Point(x, y));
        }
    }

    for (int x = 0; x < source.Width; x += 10)
    {
        TrySeed(x, 0);
        TrySeed(x, source.Height - 1);
    }
    for (int y = 0; y < source.Height; y += 10)
    {
        TrySeed(0, y);
        TrySeed(source.Width - 1, y);
    }

    bool LooksLikeBackground(Color c)
    {
        int avg = (c.R + c.G + c.B) / 3;
        int spread = Math.Max(c.R, Math.Max(c.G, c.B)) - Math.Min(c.R, Math.Min(c.G, c.B));
        return avg > 180 && spread < 45;
    }

    var dirs = new[] { new Point(1, 0), new Point(-1, 0), new Point(0, 1), new Point(0, -1) };
    while (queue.Count > 0)
    {
        var p = queue.Dequeue();
        if (p.X < 0 || p.Y < 0 || p.X >= source.Width || p.Y >= source.Height || visited[p.X, p.Y])
        {
            continue;
        }

        visited[p.X, p.Y] = true;
        var color = result.GetPixel(p.X, p.Y);
        if (!LooksLikeBackground(color))
        {
            continue;
        }

        result.SetPixel(p.X, p.Y, Color.White);
        foreach (var dir in dirs)
        {
            queue.Enqueue(new Point(p.X + dir.X, p.Y + dir.Y));
        }
    }

    return result;
}

static Bitmap ToGrayscale(Bitmap source)
{
    var result = new Bitmap(source.Width, source.Height, PixelFormat.Format24bppRgb);
    for (int y = 0; y < source.Height; y++)
    {
        for (int x = 0; x < source.Width; x++)
        {
            var c = source.GetPixel(x, y);
            int gray = (int)(0.299 * c.R + 0.587 * c.G + 0.114 * c.B);
            result.SetPixel(x, y, Color.FromArgb(gray, gray, gray));
        }
    }
    return result;
}

static Bitmap CenterPortrait(Bitmap source)
{
    int minX = source.Width, minY = source.Height, maxX = 0, maxY = 0;
    for (int y = 0; y < source.Height; y++)
    {
        for (int x = 0; x < source.Width; x++)
        {
            var c = source.GetPixel(x, y);
            if (c.R < 240 || c.G < 240 || c.B < 240)
            {
                minX = Math.Min(minX, x);
                minY = Math.Min(minY, y);
                maxX = Math.Max(maxX, x);
                maxY = Math.Max(maxY, y);
            }
        }
    }

    if (maxX <= minX || maxY <= minY)
    {
        return new Bitmap(source);
    }

    int subjectWidth = maxX - minX + 1;
    int cropWidth = Math.Min(source.Width, subjectWidth + 220);
    int cropHeight = Math.Min(source.Height, (int)(cropWidth * 1.28f));
    int centerX = (minX + maxX) / 2;
    int cropX = Math.Max(0, centerX - cropWidth / 2);
    int cropY = Math.Max(0, minY - 90);

    if (cropX + cropWidth > source.Width)
    {
        cropX = source.Width - cropWidth;
    }
    if (cropY + cropHeight > source.Height)
    {
        cropY = source.Height - cropHeight;
    }

    var crop = source.Clone(new Rectangle(cropX, cropY, cropWidth, cropHeight), PixelFormat.Format24bppRgb);
    var canvas = new Bitmap(820, 1040, PixelFormat.Format24bppRgb);
    using var g = Graphics.FromImage(canvas);
    g.SmoothingMode = SmoothingMode.AntiAlias;
    g.Clear(Color.White);

    float scale = Math.Min(canvas.Width / (float)crop.Width, canvas.Height / (float)crop.Height);
    int drawW = (int)(crop.Width * scale);
    int drawH = (int)(crop.Height * scale);
    int drawX = (canvas.Width - drawW) / 2;
    int drawY = (canvas.Height - drawH) / 2;
    g.DrawImage(crop, new Rectangle(drawX, drawY, drawW, drawH));
    crop.Dispose();
    return canvas;
}

static Bitmap BuildSpanishPage(Bitmap photo)
{
    return BuildCvPage(
        photo,
        "CURRÍCULUM VITAE",
        "Frank Jose Melgar Mijango",
        new[]
        {
            "Madrid, España",
            "Originario de El Salvador",
            "25 años",
            "Inglés medio-alto",
            "frankmelgar503@gmail.com",
            "+34 637206052"
        },
        "PERFIL",
        "Desarrollador web full stack especializado en desarrollo web y creación de soluciones completas orientadas a negocio. Experiencia en PHP, MySQL, JavaScript, HTML, CSS y Bootstrap, trabajando frontend, backend, base de datos y lógica de producto. Formación complementaria en C#, MongoDB y WordPress, con enfoque en desarrollo web profesional y e-commerce.",
        "HABILIDADES TÉCNICAS",
        new[]
        {
            "PHP, JavaScript, HTML5, CSS3, SQL y C#",
            "MySQL y MongoDB",
            "Bootstrap, WordPress, Git, GitHub, XAMPP y phpMyAdmin",
            "Desarrollo web, e-commerce y paneles administrativos",
            "Frontend, backend, integración de negocio y diseño responsive"
        },
        "FORMACIÓN",
        new[]
        {
            "Formación académica de 3 años en Desarrollo de Software en ITCA FEPADE, con enfoque en programación, bases de datos, arquitectura de software y desarrollo de aplicaciones.",
            "Estudios en C# orientados a programación orientada a objetos y fundamentos sólidos de software.",
            "Estudios en MongoDB para modelado de datos y bases de datos NoSQL.",
            "Estudios en WordPress para creación, personalización y administración de sitios web."
        },
        "PROYECTO DESTACADO",
        "SMART TECH SOLUTIONS",
        "Tienda tecnológica desarrollada con PHP y MySQL. Incluye catálogo por categorías y marcas, panel administrativo, carrito de compra, checkout y gestión de pedidos. Proyecto planteado como base comercial reutilizable para negocios digitales.",
        "FORTALEZAS",
        new[]
        {
            "Resolución de problemas",
            "Aprendizaje continuo",
            "Atención al detalle",
            "Mentalidad de producto",
            "Adaptación rápida y trabajo autónomo"
        }
    );
}

static Bitmap BuildEnglishPage(Bitmap photo)
{
    return BuildCvPage(
        photo,
        "CURRICULUM VITAE",
        "Frank Jose Melgar Mijango",
        new[]
        {
            "Madrid, Spain",
            "Originally from El Salvador",
            "25 years old",
            "Upper-intermediate English",
            "frankmelgar503@gmail.com",
            "+34 637206052"
        },
        "PROFILE",
        "Full stack web developer specialized in web development and complete business-oriented solutions. Experience with PHP, MySQL, JavaScript, HTML, CSS, and Bootstrap, working across frontend, backend, database design, and product logic. Additional studies in C#, MongoDB, and WordPress, with a strong focus on professional web development and e-commerce.",
        "TECHNICAL SKILLS",
        new[]
        {
            "PHP, JavaScript, HTML5, CSS3, SQL, and C#",
            "MySQL and MongoDB",
            "Bootstrap, WordPress, Git, GitHub, XAMPP, and phpMyAdmin",
            "Web development, e-commerce, and admin dashboards",
            "Frontend, backend, business integration, and responsive design"
        },
        "EDUCATION",
        new[]
        {
            "3 years of academic training in Software Development at ITCA FEPADE, focused on programming, databases, software architecture, and application development.",
            "Studies in C# focused on object-oriented programming and strong software fundamentals.",
            "Studies in MongoDB for data modeling and NoSQL database work.",
            "Studies in WordPress for website creation, customization, and administration."
        },
        "FEATURED PROJECT",
        "SMART TECH SOLUTIONS",
        "Technology e-commerce platform built with PHP and MySQL. Includes a category and brand-based catalog, admin panel, shopping cart, checkout, and order management. Designed as a reusable commercial foundation for digital businesses.",
        "STRENGTHS",
        new[]
        {
            "Problem solving",
            "Continuous learning",
            "Attention to detail",
            "Product mindset",
            "Fast adaptation and independent work"
        }
    );
}

static Bitmap BuildCvPage(
    Bitmap photo,
    string topLabel,
    string mainTitle,
    string[] facts,
    string profileTitle,
    string profileText,
    string skillsTitle,
    string[] skills,
    string educationTitle,
    string[] education,
    string projectTitle,
    string projectName,
    string projectText,
    string strengthsTitle,
    string[] strengths)
{
    int width = 2480;
    int height = 3508;
    int sidebarWidth = 760;
    var bitmap = new Bitmap(width, height, PixelFormat.Format24bppRgb);

    using var g = Graphics.FromImage(bitmap);
    g.SmoothingMode = SmoothingMode.AntiAlias;
    g.TextRenderingHint = System.Drawing.Text.TextRenderingHint.ClearTypeGridFit;
    g.Clear(Color.White);

    var dark = Color.FromArgb(10, 23, 41);
    var accent = Color.FromArgb(39, 110, 196);
    var lightGray = Color.FromArgb(245, 247, 250);
    var body = Color.FromArgb(56, 69, 84);

    using var darkBrush = new SolidBrush(dark);
    using var accentBrush = new SolidBrush(accent);
    using var whiteBrush = new SolidBrush(Color.White);
    using var bodyBrush = new SolidBrush(body);
    using var lightBrush = new SolidBrush(lightGray);
    using var dividerPen = new Pen(Color.FromArgb(220, 225, 232), 3f);

    g.FillRectangle(darkBrush, 0, 0, sidebarWidth, height);
    g.FillRectangle(lightBrush, sidebarWidth, 0, width - sidebarWidth, 280);

    using var titleFont = new Font("Segoe UI", 26, FontStyle.Bold);
    using var nameFont = new Font("Segoe UI", 34, FontStyle.Bold);
    using var roleFont = new Font("Segoe UI", 24, FontStyle.Bold);
    using var sectionFont = new Font("Segoe UI", 18, FontStyle.Bold);
    using var bodyFont = new Font("Segoe UI", 12.5f, FontStyle.Regular);
    using var sidebarFactFont = new Font("Segoe UI", 12f, FontStyle.Regular);
    using var sidebarSectionFont = new Font("Segoe UI", 16f, FontStyle.Bold);

    DrawCircularPhoto(g, photo, new Rectangle(125, 90, 500, 500));

    g.DrawString(topLabel, titleFont, whiteBrush, new RectangleF(95, 640, 560, 50));
    g.DrawString(mainTitle, nameFont, whiteBrush, new RectangleF(95, 700, 560, 110));
    g.DrawString("Full Stack Web Developer", roleFont, accentBrush, new RectangleF(95, 860, 560, 70));

    int factY = 960;
    foreach (var fact in facts)
    {
        g.FillEllipse(accentBrush, 96, factY + 12, 10, 10);
        g.DrawString(fact, sidebarFactFont, whiteBrush, new RectangleF(125, factY, 540, 42));
        factY += 58;
    }

    DrawSidebarSection(g, sidebarSectionFont, bodyFont, whiteBrush, accentBrush, 95, 1340, skillsTitle, skills);
    DrawSidebarSection(g, sidebarSectionFont, bodyFont, whiteBrush, accentBrush, 95, 2270, strengthsTitle, strengths);

    int mainX = sidebarWidth + 90;
    g.DrawString(mainTitle, new Font("Segoe UI", 28, FontStyle.Bold), darkBrush, new RectangleF(mainX, 70, 1200, 60));
    g.DrawString("Web Development Specialist", new Font("Segoe UI", 14, FontStyle.Regular), accentBrush, new RectangleF(mainX, 140, 800, 40));

    int y = 340;
    y = DrawMainSection(g, sectionFont, bodyFont, darkBrush, bodyBrush, dividerPen, mainX, y, 1500, profileTitle, new[] { profileText });
    y = DrawMainSection(g, sectionFont, bodyFont, darkBrush, bodyBrush, dividerPen, mainX, y, 1500, educationTitle, education);
    y = DrawMainSection(g, sectionFont, bodyFont, darkBrush, bodyBrush, dividerPen, mainX, y, 1500, projectTitle, new[] { projectName + "\n" + projectText });

    return bitmap;
}

static void DrawCircularPhoto(Graphics g, Bitmap photo, Rectangle bounds)
{
    using var path = new GraphicsPath();
    path.AddEllipse(bounds);
    var state = g.Save();
    g.SetClip(path);
    g.DrawImage(photo, bounds);
    g.Restore(state);
    using var pen = new Pen(Color.White, 10f);
    g.DrawEllipse(pen, bounds);
}

static void DrawSidebarSection(Graphics g, Font titleFont, Font bodyFont, Brush textBrush, Brush accentBrush, int x, int y, string title, string[] items)
{
    g.DrawString(title, titleFont, textBrush, new RectangleF(x, y, 560, 34));
    int itemY = y + 60;
    foreach (var item in items)
    {
        g.FillEllipse(accentBrush, x, itemY + 12, 10, 10);
        DrawWrappedText(g, item, bodyFont, textBrush, new RectangleF(x + 28, itemY, 560, 110), 1.35f);
        itemY += 110;
    }
}

static int DrawMainSection(Graphics g, Font titleFont, Font bodyFont, Brush titleBrush, Brush bodyBrush, Pen dividerPen, int x, int y, int width, string title, string[] paragraphs)
{
    g.DrawString(title, titleFont, titleBrush, new RectangleF(x, y, width, 36));
    y += 52;
    foreach (var paragraph in paragraphs)
    {
        var height = DrawWrappedText(g, paragraph, bodyFont, bodyBrush, new RectangleF(x, y, width, 400), 1.6f);
        y += height + 20;
    }
    g.DrawLine(dividerPen, x, y, x + width, y);
    return y + 40;
}

static int DrawWrappedText(Graphics g, string text, Font font, Brush brush, RectangleF bounds, float lineSpacing)
{
    var words = text.Split(' ');
    var lines = new List<string>();
    var current = new StringBuilder();

    foreach (var word in words)
    {
        var candidate = current.Length == 0 ? word : current + " " + word;
        var size = g.MeasureString(candidate, font, (int)bounds.Width);
        if (size.Width > bounds.Width - 10 && current.Length > 0)
        {
            lines.Add(current.ToString());
            current.Clear();
            current.Append(word);
        }
        else
        {
            current.Clear();
            current.Append(candidate);
        }
    }

    if (current.Length > 0)
    {
        lines.Add(current.ToString());
    }

    float y = bounds.Y;
    float lineHeight = font.GetHeight(g) * lineSpacing;
    foreach (var line in lines)
    {
        g.DrawString(line, font, brush, new PointF(bounds.X, y));
        y += lineHeight;
    }

    return (int)(y - bounds.Y);
}

static void WritePdfFromJpegs(string pdfPath, List<string> jpegPaths, List<(int Width, int Height)> sizes)
{
    using var fs = new FileStream(pdfPath, FileMode.Create, FileAccess.Write);
    using var writer = new BinaryWriter(fs, Encoding.ASCII);
    var offsets = new Dictionary<int, long>();
    var objects = new SortedDictionary<int, byte[]>();

    writer.Write(Encoding.ASCII.GetBytes("%PDF-1.4\n%\u00e2\u00e3\u00cf\u00d3\n"));

    var pageObjectIds = new List<int>();
    int currentObj = 3;

    objects[1] = Encoding.ASCII.GetBytes("<< /Type /Catalog /Pages 2 0 R >>");

    for (int i = 0; i < jpegPaths.Count; i++)
    {
        int imageObj = currentObj++;
        int contentObj = currentObj++;
        int pageObj = currentObj++;
        pageObjectIds.Add(pageObj);

        var jpegBytes = File.ReadAllBytes(jpegPaths[i]);
        var imageStream = new MemoryStream();
        imageStream.Write(Encoding.ASCII.GetBytes($"<< /Type /XObject /Subtype /Image /Width {sizes[i].Width} /Height {sizes[i].Height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {jpegBytes.Length} >>\nstream\n"));
        imageStream.Write(jpegBytes);
        imageStream.Write(Encoding.ASCII.GetBytes("\nendstream"));
        objects[imageObj] = imageStream.ToArray();

        string drawingCommands = $"q\n595 0 0 842 0 0 cm\n/Im{i + 1} Do\nQ\n";
        var drawingBytes = Encoding.ASCII.GetBytes(drawingCommands);
        var contentStream = new MemoryStream();
        contentStream.Write(Encoding.ASCII.GetBytes($"<< /Length {drawingBytes.Length} >>\nstream\n"));
        contentStream.Write(drawingBytes);
        contentStream.Write(Encoding.ASCII.GetBytes("endstream"));
        objects[contentObj] = contentStream.ToArray();

        objects[pageObj] = Encoding.ASCII.GetBytes($"<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /XObject << /Im{i + 1} {imageObj} 0 R >> >> /Contents {contentObj} 0 R >>");
    }

    var kids = string.Join(" ", pageObjectIds.Select(id => $"{id} 0 R"));
    objects[2] = Encoding.ASCII.GetBytes($"<< /Type /Pages /Count {pageObjectIds.Count} /Kids [ {kids} ] >>");

    foreach (var entry in objects)
    {
        offsets[entry.Key] = fs.Position;
        writer.Write(Encoding.ASCII.GetBytes($"{entry.Key} 0 obj\n"));
        writer.Write(entry.Value);
        writer.Write(Encoding.ASCII.GetBytes("\nendobj\n"));
    }

    long xrefPosition = fs.Position;
    int maxObject = objects.Keys.Max();
    writer.Write(Encoding.ASCII.GetBytes($"xref\n0 {maxObject + 1}\n"));
    writer.Write(Encoding.ASCII.GetBytes("0000000000 65535 f \n"));
    for (int i = 1; i <= maxObject; i++)
    {
        long offset = offsets.TryGetValue(i, out var value) ? value : 0;
        var state = offsets.ContainsKey(i) ? "n" : "f";
        writer.Write(Encoding.ASCII.GetBytes($"{offset:D10} 00000 {state} \n"));
    }
    writer.Write(Encoding.ASCII.GetBytes($"trailer\n<< /Size {maxObject + 1} /Root 1 0 R >>\nstartxref\n{xrefPosition}\n%%EOF"));
}
