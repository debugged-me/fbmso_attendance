<!DOCTYPE html>
<html lang="en">

<head>
    <style>
        /* General reset */
        body {
            font-family: 'Calibri', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1;
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            background: #ffffff;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }

        .letterhead img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .subhead h4 {
            text-align: center;
            font-size: 1.2rem;
            margin: 20px 0;
            color: #4a4a4a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th {
            text-align: left;
            background: #f0f0f0;
            padding: 6px;
            border-bottom: 2px solid #333;
            font-size: 0.9rem;
            color: #333;
        }

        table td {
            padding: 8px;
            vertical-align: top;
            font-size: 0.9rem;
            color: #555;
            background: none;
        }

        /* table tr:nth-child(odd) {
            background-color: #fafafa;
        } */

        table tr td:first-child {
            font-weight: bold;
            color: #4a4a4a;
            width: 35%;
        }

        .form {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }

        .rightbox {
            float: right;
            width: 150px;
            height: 150px;
            border: 2px dashed #666;
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-radius: 8px;
            margin: 5px 0;
        }

        .rightbox p {
            font-size: 0.8rem;
            color: #666;
        }

        .clearfix {
            clear: both;
        }

        p {
            margin: 0 0 5px;
        }


        .sig {
            float: left;
            width: 50%;
            /* Occupy half of the container */
            text-align: center;
            /* Center the second <p> */
        }

        .sign {
            float: right;
            width: 50%;
            /* Occupy the other half of the container */
            text-align: center;
            /* Center the second <p> */
        }

        .sig p:first-child,
        .sign p:first-child {
            text-align: left;
            /* Align the name and date text to the left */
        }

        .sig p#center,
        .sign p#center {
            margin-top: 10px;
            text-align: center;
            /* Ensure the second <p> is centered */
        }

        .clearfix {
            clear: both;
            /* To prevent floating elements from overlapping */
        }

        @media print {
            body {
                background: #fff;
                color: #000;
                font-size: 12px;
                line-height: 1;
            }

            .container {
                box-shadow: none;
                border: none;
                padding: 0;
            }

            .rightbox {
                border: 2px dashed #333;
                background-color: transparent;
            }
        }
    </style>
    <script src="<?= base_url('assets/js/anti-inspect.js?v=1'); ?>"></script>
</head>

<body>
    <div class="container">
        <div class="letterhead">

            <img src="<?= base_url('upload/banners/' . $data1->letterhead_web); ?>" alt="Letterhead"
                style="max-width: 100%; height: auto; display: block; margin: 0 auto;">



            <br />
        </div>
        <div class="subhead center">
            <h4>Student Profile Form</h4>
        </div>

        <table>
            <tr>
                <th colspan="2">I. APPLICATION FOR ENROLLMENT</th>
            </tr>
            <tr>
                <td>Student Number</td>
                <td>: <?php echo $data[0]->StudentNumber; ?></td>
            </tr>
            <tr>
                <td>Semester</td>
                <td>: <?php echo $sem; ?></td>
            </tr>
            <tr>
                <td>Academic Year</td>
                <td>: <?php echo $sy; ?></td>
            </tr>
            <tr>
                <td>Courses</td>
                <td>:
                    <?php echo $data[0]->course; ?><br>

                </td>
            </tr>
        </table>
        <table>
            <tr>
                <th colspan="2">II. PERSONAL INFORMATION</th>
            </tr>
            <tr>
                <td>Student's Name</td>
                <td>: <?php echo $data[0]->LastName; ?>, <?php echo $data[0]->FirstName; ?> <?php echo $data[0]->MiddleName; ?></td>
            </tr>

            <tr>
                <td>Date of Birth</td>
                <td>: <?php echo date('F d, Y', strtotime($data[0]->birthDate)); ?></td>
            </tr>
            <tr>
                <td>Sex</td>
                <td>: <?php echo $data[0]->Sex; ?></td>
            </tr>
            <tr>
                <td>Place of Birth</td>
                <td>: <?php echo $data[0]->BirthPlace; ?></td>
            </tr>
            <tr>
                <td>Civil Status</td>
                <td>: <?php echo $data[0]->CivilStatus; ?></td>
            </tr>
            <tr>
                <td>Religion</td>
                <td>: <?php echo $data[0]->Religion; ?></td>
            </tr>
            <tr>
                <td>Tribe/Ethnic Group</td>
                <td>: <?php echo $data[0]->ethnicity; ?></td>
            </tr>
            <tr>
                <td>Email Address</td>
                <td>: <?php echo $data[0]->email; ?></td>
            </tr>
            <tr>
                <td>Contact Number</td>
                <td>: <?php echo $data[0]->contactNo; ?></td>
            </tr>
            <tr>
                <td>Permanent Address</td>
                <td>: <?php echo $data[0]->sitio; ?>, <?php echo $data[0]->brgy; ?>, <?php echo $data[0]->city; ?>, <?php echo $data[0]->province; ?></td>
            </tr>
        </table>
        <table>
            <tr>
                <th colspan="2">III. FAMILY BACKGROUND</th>
            </tr>
            <tr>
                <td>Spouse (if married)</td>
                <td>: <?php echo $data[0]->spouse; ?></td>
            </tr>
            <tr>
                <td>No. of Children</td>
                <td>: <?php echo $data[0]->children; ?></td>
            </tr>
            <tr>
                <td>Contact Number (Spouse)</td>
                <td>: <?php echo $data[0]->spouseContact; ?></td>
            </tr>
            <tr>
                <td>Father's Name</td>
                <td>: <?php echo $data[0]->father; ?></td>
            </tr>
            <tr>
                <td>Father's Occupation</td>
                <td>: <?php echo $data[0]->fOccupation; ?></td>
            </tr>
            <tr>
                <td>Father's Contact Number</td>
                <td>: <?php echo $data[0]->fatherContact; ?></td>
            </tr>
            <tr>
                <td>Mother's Name</td>
                <td>: <?php echo $data[0]->mother; ?></td>
            </tr>
            <tr>
                <td>Mother's Occupation</td>
                <td>: <?php echo $data[0]->mOccupation; ?></td>
            </tr>
            <tr>
                <td>Mother's Contact Number</td>
                <td>: <?php echo $data[0]->motherContact; ?></td>
            </tr>
            <tr>
                <td>Emergency Contact Person</td>
                <td>: <?php echo $data[0]->guardian; ?></td>
            </tr>
            <tr>
                <td>Emergency Contact Number</td>
                <td>: <?php echo $data[0]->guardianContact; ?></td>
            </tr>
            <tr>
                <td>Emergency Contact Address</td>
                <td>: <?php echo $data[0]->guardianAddress; ?></td>
            </tr>
        </table>

        <table>
            <tr>
                <th colspan="2">IV. EDUCATIONAL BACKGROUND</th>
            </tr>
            <tr>
                <td>Elementary School</td>
                <td>: <?php echo $data[0]->elementary; ?>
                    <?php if (!empty($data[0]->elemGraduated)): ?>
                        (Year Graduated: <?php echo $data[0]->elemGraduated; ?>)
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>High School</td>
                <td>: <?php echo $data[0]->secondary; ?>
                    <?php if (!empty($data[0]->secondaryGraduated)): ?>
                        (Year Graduated: <?php echo $data[0]->secondaryGraduated; ?>)
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Vocational School</td>
                <td>: <?php echo $data[0]->vocational; ?>
                    <?php if (!empty($data[0]->vocationalGraduated)): ?>
                        (Year Graduated: <?php echo $data[0]->vocationalGraduated; ?>)
                    <?php endif; ?>
                </td>
            </tr>

        </table>


        <h4 style="text-align: center;">PLEDGE</h4>
        <p style="text-align: justify;">
            In consideration of my admission to <?php echo $data1->SchoolName; ?>, I hereby promise and pledge to abide by and comply with all the rules and regulations laid down by competent authority in <?php echo $data1->SchoolName; ?>, and in the institute in which I am enrolled.
            By providing information to the Admission Office, I am confirming that all data supplied are true, complete and correct.
            I understand that giving false and lacking information will make me ineligible for admission, and that <?php echo $data1->SchoolName; ?> reserves the right to revise any decision made on the basis of the Information I have provided,
            should the information be found to be untrue and incorrect.

        </p>
        <br>
        <h4 style="text-align: center;">STUDENT'S DATA PRIVACY CONSENT</h4>
        <p style="text-align: justify;">
            As a student, I understand and agree that by providing my personal data, I am agreeing to the Data Privacy Policy and Terms of <?php echo $data1->SchoolName; ?> and giving my full consent to the
            institution and its affiliates as well as its partners and service providers, if any, to collect, store, access and/or process any personal data I may provide herein, whether manually or electronically for the period AY_ until the Academic Year of my graduation or withdrawal/transfer
            from the institution, for the purpose of my admission, enrollment, research and other legitimate records processing under this office concerned. I acknowledge that the collection and processing of my personal data is necessary for such purposes.
        </p>

        <br>
        <br>
        <br>

        <div class="sig">
            <p id="center"><?php echo $data[0]->FirstName; ?> <?php echo $data[0]->MiddleName; ?> <?php echo $data[0]->LastName; ?></p>
            <p id="center">Student's Signature over Printed Name</p>
        </div>

        <div class="sign">
            <p id="center">Date Signed:___________________</p>
        </div>

        <div class="clearfix"></div>
    </div>
</body>

</html>